<?php

namespace BS\ExtendedSearch\Source\PostProcessor;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\IPostProcessor;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\PostProcessor;
use BS\ExtendedSearch\Wildcarder;
use Elastica\Result;

class Base implements IPostProcessor {
	/**
	 * @var PostProcessor
	 */
	protected $base;

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

	public function process( Result &$result, Lookup $lookup ) {
		// No need to do this in AC, since ngram is already tokenizes the term
		if ( $this->base->getType() === Backend::QUERY_TYPE_SEARCH ) {
			$this->percentageBoost( $result, $lookup );
			$this->base->requestReSort();
		}
	}

	public function percentageBoost( Result &$result, Lookup $lookup ) {
		if ( !$this->isScoreSorting( $lookup ) ) {
			// If user sorts by something else by relevance
			return;
		}
		if ( $this->isRegex( $lookup ) ) {
			// We don't check match percentage on regex
			return;
		}
		$score = $result->getScore();
		if ( !is_float( $score ) ) {
			return;
		}
		$matchPercent = $this->getMatchPercent( $result, $lookup );
		$factor = $matchPercent * 0.5;

		$result->setParam( '_score', $score + ( $score * $factor ) );
	}

	private function getMatchPercent( $result, $lookup ) {
		$title = strtolower( $this->getTitleField( $result ) );
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
		return '';
	}

	/**
	 * @param Result $result
	 * @return string
	 */
	protected function getTitleField( $result ) {
		return $result->getData()['basename'];
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
