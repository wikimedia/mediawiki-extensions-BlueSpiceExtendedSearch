<?php

namespace BS\ExtendedSearch\Source\PostProcessor;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\SearchResult;
use MediaWiki\Config\ConfigException;

class WikiPage extends Base {

	/**
	 *
	 * @param SearchResult &$result
	 * @param Lookup $lookup
	 */
	public function process( SearchResult &$result, Lookup $lookup ) {
		parent::process( $result, $lookup );

		if ( !$this->isScoreSorting( $lookup ) ) {
			// If user sorts by something else by relevance
			return;
		}
		if ( $this->mTimeBoost( $result, $lookup ) ) {
			$this->postProcessorRunner->requestReSort();
		}
		if ( $this->supressFilePages( $result ) ) {
			$this->postProcessorRunner->requestReSort();
		}
	}

	/**
	 * @param SearchResult $result
	 * @return string
	 */
	protected function getTitleFieldName( $result ) {
		if ( $result->getType() !== 'wikipage' ) {
			return parent::getTitleFieldName( $result );
		}
		$data = $result->getData();
		if ( $data['display_title'] !== '' ) {
			return 'display_title';
		}
		return 'prefixed_title';
	}

	/**
	 * Boost the result based on its modification time.
	 * More recent results get more boost.
	 *
	 * @param SearchResult $result
	 * @param Lookup $lookup
	 * @return bool
	 * @throws ConfigException
	 */
	protected function mTimeBoost( SearchResult $result, Lookup $lookup ) {
		if ( $result->getType() !== 'wikipage' ) {
			return false;
		}
		$portionOfScore = ( $this->postProcessorRunner->getType() === Backend::QUERY_TYPE_SEARCH ) ? 1 : 0.3;
		$mTime = $result->getData()['mtime'];
		$mTime = wfTimestamp( TS_UNIX, $mTime );
		$now = wfTimestamp();
		$diff = (int)( $now - $mTime ) / 60;
		if ( $diff > 43800 ) {
			// Disregard all pages that were modified more than a month ago (43800 min)
			// Those are probably not useful anyway, and they will produce incorrect boost curve
			return true;
		}
		// Get normalized relevance - nearly 1 for very recent pages and nearly 0 for pages near a month old
		$relevance = 1 - ( round( ( $diff - 0 ) / ( 43801 - 0 ), 3 ) );
		// Portion of score controls how much of the score can be boosted
		// This is different for AC and fulltext due to how scoring is determined
		$boostFactor = (float)$this->postProcessorRunner->getConfig()->get( 'ESRecentBoostFactor' );
		if ( $boostFactor === 0 ) {
			// Would produce 0 score
			return true;
		}
		$score = $result->getScore();
		if ( !is_numeric( $score ) ) {
				$score = 1;
		}
		$boostValue = round( ( $score * $portionOfScore ) * ( $relevance * $boostFactor ), 2 );

		$result->setParam( '_score', $score + $boostValue );
		return true;
	}

	/**
	 * Reduce score pages in NS_FILE
	 *
	 * @param SearchResult $result
	 *
	 * @return bool
	 */
	private function supressFilePages( SearchResult $result ) {
		if ( $result->getType() !== 'wikipage' ) {
			return false;
		}
		$ns = (int)$result->getData()['namespace'];
		if ( $ns !== NS_FILE ) {
			return false;
		}
		$boostReduceFactor = 0.5;
		$boostReduce = round( $result->getScore() * $boostReduceFactor, 2 );
		$result->setParam( '_score', $result->getScore() - $boostReduce );
		return true;
	}
}
