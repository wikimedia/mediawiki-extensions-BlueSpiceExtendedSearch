<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class BaseAutocompleteSourceFields extends Base {

	public function apply() {
		$this->oLookup->addSourceField( 'basename' );
		$this->oLookup->addSourceField( 'uri' );
		$this->oLookup->addSourceField( 'filename' );
	}

	public function undo() {
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_AUTOCOMPLETE ];
	}
}
