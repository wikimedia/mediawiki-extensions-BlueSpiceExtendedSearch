<?php

namespace BS\ExtendedSearch\Source\LookupModifier;
use BS\ExtendedSearch\Lookup;

class BaseSortByID extends Base {

	public function apply() {
		$this->oLookup->addSort( 'sortable_id', Lookup::SORT_DESC );
	}

	public function undo() {
		$this->oLookup->removeSort( 'sortable_id' );
	}

}
