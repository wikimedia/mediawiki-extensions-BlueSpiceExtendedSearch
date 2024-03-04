<?php

namespace BS\ExtendedSearch\Source\PostProcessor;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Plugin\IPostProcessor;
use BS\ExtendedSearch\PostProcessor;
use BS\ExtendedSearch\SearchResult;
use BS\ExtendedSearch\Wildcarder;
use ConfigException;

class Base implements IPostProcessor {
	/**
	 * @var PostProcessor
	 */
	protected $postProcessorRunner;

	/**
	 * IPostProcessor constructor.
	 * @param PostProcessor $postProcessorRunner
	 */
	public function __construct( PostProcessor $postProcessorRunner ) {
		$this->postProcessorRunner = $postProcessorRunner;
	}

	/**
	 * @param SearchResult &$result
	 * @param Lookup $lookup
	 */
	public function process( SearchResult &$result, Lookup $lookup ) {
		if ( !$this->isScoreSorting( $lookup ) ) {
			// If user sorts by something else by relevance
			return;
		}
		if ( $this->postProcessorRunner->getType() === Backend::QUERY_TYPE_SEARCH ) {
			if ( $this->fulltextPercentageBoost( $result, $lookup ) ) {
				$this->postProcessorRunner->requestReSort();
			}
		} elseif ( $this->postProcessorRunner->getType() === Backend::QUERY_TYPE_AUTOCOMPLETE ) {
			if ( $this->autocompletePercentageBoost( $result, $lookup ) ) {
				$this->postProcessorRunner->requestReSort();
			}
		}
	}

	/**
	 * Apply percent boost to autocomplete query
	 *
	 * @param SearchResult &$result
	 * @param Lookup $lookup
	 * @return bool false on fail/not-applicable
	 */
	protected function autocompletePercentageBoost( SearchResult &$result, Lookup $lookup ) {
		return $this->percentageBoost( $result, $lookup );
	}

	/**
	 * Apply percent boost to fulltext query
	 *
	 * @param SearchResult &$result
	 * @param Lookup $lookup
	 * @return bool false on fail/not-applicable
	 */
	protected function fulltextPercentageBoost( SearchResult &$result, Lookup $lookup ) {
		if ( $this->isRegex( $lookup ) ) {
			// We don't check match percentage on regex
			return false;
		}
		return $this->percentageBoost( $result, $lookup );
	}

	/**
	 * @param SearchResult &$result
	 * @param Lookup $lookup
	 * @return bool
	 */
	private function percentageBoost( SearchResult &$result, Lookup $lookup ) {
		$score = $result->getScore();
		if ( !is_float( $score ) ) {
			return false;
		}
		$matchPercent = $this->getMatchPercent( $result, $lookup );
		$boostFactor = (float)$this->postProcessorRunner->getConfig()->get( 'ESMatchPercentBoostFactor' );
		$factor = $matchPercent * $boostFactor;

		$result->setParam( '_score', $score + ( $score * $factor ) );

		return true;
	}

	/**
	 * @param SearchResult $result
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
	 * @return array
	 */
	private function getSearchTermTokens( $lookup ): array {
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
		if ( $this->postProcessorRunner->getType() === Backend::QUERY_TYPE_SEARCH ) {
			$qs = $lookup->getQueryString();
			if ( is_array( $qs ) ) {
				return $qs[ 'query' ];
			}
			return $qs;
		}
		if ( $this->postProcessorRunner->getType() === Backend::QUERY_TYPE_AUTOCOMPLETE ) {
			return isset( $lookup['query']['bool']['must']['multi_match']['query'] ) ?
				$lookup['query']['bool']['must']['multi_match']['query'] : '';
		}
		return '';
	}

	/**
	 * Name of the field on which to base match percent boost
	 *
	 * @param SearchResult $result
	 * @return string
	 */
	protected function getTitleFieldName( $result ) {
		return 'basename';
	}

	/**
	 * @param SearchResult $result
	 * @return string
	 * @throws ConfigException
	 */
	protected function getTitleFieldValue( $result ) {
		$configField = $this->postProcessorRunner->getConfig()->get( 'ESMatchPercentTitleField' );
		$fieldName = $configField ?: $this->getTitleFieldName( $result );
		$data = $result->getData();
		return $data[$fieldName] ?? 'basename';
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
