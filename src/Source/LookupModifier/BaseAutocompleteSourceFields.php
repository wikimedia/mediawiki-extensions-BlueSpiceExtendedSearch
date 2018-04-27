<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseAutocompleteSourceFields extends Base {

	public function apply() {
		$this->oLookup->addSourceField( 'basename' );
	}

	public function undo() {
	}

}
