<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Lookup;

class BaseSortByID extends LookupModifier {

	public function apply() {
		$this->lookup->addSort( 'sortable_id', Lookup::SORT_DESC );
	}

	public function undo() {
		$this->lookup->removeSort( 'sortable_id' );
	}

}
