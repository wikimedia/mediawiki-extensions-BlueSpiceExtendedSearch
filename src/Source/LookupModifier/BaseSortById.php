<?php

namespace BS\ExtendedSearch\Source\LookupModifier;
use BS\ExtendedSearch\Lookup;

class BaseSortByID extends Base {

	public function apply() {
		$this->oLookup->addSort( '_id', Lookup::SORT_DESC );
	}

	public function undo() {
		$this->oLookup->removeSort( '_id' );
	}

}
