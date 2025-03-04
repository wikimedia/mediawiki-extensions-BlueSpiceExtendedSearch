<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class FileAutocompleteSourceFields extends LookupModifier {

	/**
	 * @return void
	 */
	public function apply() {
		$this->lookup->addSourceField( 'filename' );
		$this->lookup->addSourceField( 'extension' );
		$this->lookup->addSourceField( 'mime_type' );
	}

	/**
	 * @return void
	 */
	public function undo() {
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_AUTOCOMPLETE ];
	}

}
