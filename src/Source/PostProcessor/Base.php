<?php

namespace BS\ExtendedSearch\Source\PostProcessor;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\IPostProcessor;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\PostProcessor;
use BS\ExtendedSearch\Wildcarder;
use ConfigException;
use Elastica\Result;

class Base implements IPostProcessor {
	/**
	 * @var PostProcessor
	 */
	protected $base;

	/**
	 *
	 * @param PostProcessor $base
	 * @return IPostProcessor
	 */
	public static function factory( PostProcessor $base ) {
		return new static( $base );
	}

	/**
	 * IPostProcessor constructor.
	 * @param PostProcessor $base
	 */
	protected function __construct( PostProcessor $base ) {
		$this->base = $base;
	}

	/**
	 * @param Result &$result
	 * @param Lookup $lookup
	 */
	public function process( Result &$result, Lookup $lookup ) {
		if ( !$this->isScoreSorting( $lookup ) ) {
			// If user sorts by something else by relevance
			return;
		}
		if ( $this->base->getType() === Backend::QUERY_TYPE_SEARCH ) {
			if ( $this->fulltextPercentageBoost( $result, $lookup ) ) {
				$this->base->requestReSort();
			}
		} elseif ( $this->base->getType() === Backend::QUERY_TYPE_AUTOCOMPLETE ) {
			if ( $this->autocompletePercentageBoost( $result, $lookup ) ) {
				$this->base->requestReSort();
			}
		}
	}

	/**
	 * Apply percent boost to autocomplete query
	 *
	 * @param Result &$result
	 * @param Lookup $lookup
	 * @return bool false on fail/not-applicable
	 */
	protected function autocompletePercentageBoost( Result &$result, Lookup $lookup ) {
		return $this->percentageBoost( $result, $lookup );
	}

	/**
	 * Apply percent boost to fulltext query
	 *
	 * @param Result &$result
	 * @param Lookup $lookup
	 * @return bool false on fail/not-applicable
	 */
	protected function fulltextPercentageBoost( Result &$result, Lookup $lookup ) {
		if ( $this->isRegex( $lookup ) ) {
			// We don't check match percentage on regex
			return false;
		}
		return $this->percentageBoost( $result, $lookup );
	}

	/**
	 * @param Result &$result
	 * @param Lookup $lookup
	 * @return bool
	 */
	private function percentageBoost( Result &$result, Lookup $lookup ) {
		$score = $result->getScore();
		if ( !is_float( $score ) ) {
			return false;
		}
		$matchPercent = $this->getMatchPercent( $result, $lookup );
		$boostFactor = (float)$this->base->getConfig()->get( 'ESMatchPercentBoostFactor' );
		$factor = $matchPercent * $boostFactor;

		$result->setParam( '_score', $score + ( $score * $factor ) );

		return true;
	}

	/**
	 * @param Result $result
	 * @param Lookup $lookup
	 * @return int
	 */
	private function getMatchPercent( $result, $lookup ) {
		$title = strtolower( $this->getTitleFieldValue( $result ) );
		$tokens = $this->getSearchTermTokens( $lookup );
		if ( empty( $tokens ) ) {
			return 0;
		}

		$matchCount = 0;
		foreach ( $tokens as $token ) {
			if ( $token == '' ) {
				continue;
			}
			if ( strpos( $title, $token ) !== false ) {
				$matchCount += strlen( $token );
			}
		}
		if ( $matchCount >= strlen( $title ) ) {
			return 1;
		}
		if ( $matchCount === 0 ) {
			return 0;
		}

		return $matchCount / strlen( $title );
	}

	/**
	 * @param Lookup $lookup
	 * @return string
	 */
	private function getSearchTermTokens( $lookup ) {
		$term = $this->getTermFromLookup( $lookup );
		$wildcarder = Wildcarder::factory( $term );
		$term = $wildcarder->replaceSeparators( ' ' );
		$term = strtolower( $term );
		return explode( ' ', $term );
	}

	/**
	 * @param Lookup $lookup
	 * @return string
	 */
	protected function getTermFromLookup( $lookup ) {
		if ( $this->base->getType() === Backend::QUERY_TYPE_SEARCH ) {
			$qs = $lookup->getQueryString();
			if ( is_array( $qs ) ) {
				return $qs[ 'query' ];
			}
			return $qs;
		}
		if ( $this->base->getType() === Backend::QUERY_TYPE_AUTOCOMPLETE ) {
			return isset( $lookup['query']['bool']['must']['match']['ac_ngram']['query'] ) ?
				$lookup['query']['bool']['must']['match']['ac_ngram']['query'] : '';
		}
		return '';
	}

	/**
	 * Name of the field on which to base match percent boost
	 *
	 * @param Result $result
	 * @return string
	 */
	protected function getTitleFieldName( $result ) {
		return 'basename';
	}

	/**
	 * @param Result $result
	 * @return string
	 * @throws ConfigException
	 */
	protected function getTitleFieldValue( $result ) {
		$configField = $this->base->getConfig()->get( 'ESMatchPercentTitleField' );
		$fieldName = $configField ? $configField : $this->getTitleFieldName( $result );
		return $result->getData()[$fieldName];
	}

	/**
	 * Check if current sort is by relevance (_score)
	 *
	 * @param Lookup $lookup
	 * @return bool
	 */
	protected function isScoreSorting( $lookup ) {
		$sort = $lookup->getSort();
		if ( is_array( $sort ) && isset( $sort[0] ) ) {
			$primarySort = $sort[0];
			$field = array_keys( $primarySort )[0];
			return $field === '_score';
		}
		// If sort is not set, it defaults to _score
		return true;
	}

	/**
	 * Check if current search term is a regex
	 *
	 * @param Lookup $lookup
	 * @return bool
	 */
	protected function isRegex( $lookup ) {
		$term = $this->getTermFromLookup( $lookup );
		$wildcarder = Wildcarder::factory( $term );
		if ( $wildcarder->containsOperators() ) {
			return true;
		}
		return false;
	}
}
