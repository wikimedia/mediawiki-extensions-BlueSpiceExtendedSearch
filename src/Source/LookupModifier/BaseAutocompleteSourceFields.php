<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseAutocompleteSourceFields extends Base {

	public function apply() {
		$this->oLookup->addSourceField( 'basename' );
		$this->oLookup->addSourceField( 'uri' );
	}

	public function undo() {
	}

}
