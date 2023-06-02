<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class BaseAutocompleteSourceFields extends LookupModifier {

	public function apply() {
		$this->lookup->addSourceField( 'basename' );
		$this->lookup->addSourceField( 'uri' );
		$this->lookup->addSourceField( 'filename' );
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
