<?php

namespace BS\ExtendedSearch\Source\LookupModifier;
use BS\ExtendedSearch\Lookup;

class BaseScoreSortWhenShould extends Base {

	/**
	 * In order for "should" clause to make sense
	 * sorting by "_score" must always be first - also must be first!
	 */
	public function apply() {
		if( empty( $this->oLookup->getShould() ) ) {
			return;
		}

		$currentSort = $this->oLookup->getSort();
		array_unshift( $currentSort, ['_score' => ['order' => Lookup::SORT_DESC]] );
		$this->oLookup->setSort( $currentSort );
	}
}

