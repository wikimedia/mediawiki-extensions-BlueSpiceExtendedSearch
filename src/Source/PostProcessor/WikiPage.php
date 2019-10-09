<?php

namespace BS\ExtendedSearch\Source\PostProcessor;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use Elastica\Result;

class WikiPage extends Base {

	public function process( Result &$result, Lookup $lookup ) {
		parent::process( $result, $lookup );
		if ( $this->mTimeBoost( $result, $lookup ) ) {
			$this->base->requestReSort();
		}
	}

	/**
	 * @param Result $result
	 * @return string
	 */
	protected function getTitleField( $result ) {
		$data = $result->getData();
		if ( $data['display_title'] !== '' ) {
			return $data['display_title'];
		}
		return $data['prefixed_title'];
	}

	protected function mTimeBoost( Result $result, Lookup $lookup ) {
		if ( $result->getType() !== 'wikipage' ) {
			return false;
		}
		$portionOfScore = $this->base->getType() === Backend::QUERY_TYPE_SEARCH ? 1 : 0.3;
		$mTime = $result->getData()['mtime'];
		$mTime = wfTimestamp( TS_UNIX, $mTime );
		$now = wfTimestamp();
		$diff = (int)( $now - $mTime ) / 60;
		if ( $diff === 0 ) {
			// Impossible, but just to be safe
			return false;
		}
		// Get normalized relevance - nearly 1 for very recent pages and nearly 0 for very old ones
		$relevance = 100 / $diff;
		// Portion of score controls how much of the score can be boosted
		// This is different for AC and fulltext due to how scoring is determined
		// If $portionOfScore is 1, it means that very recent pages can almost double the score
		$boostValue = round( ( $result->getScore() * $portionOfScore ) * $relevance, 2 );
		$result->setParam( '_score', $result->getScore() + $boostValue );
		return true;
	}
}
