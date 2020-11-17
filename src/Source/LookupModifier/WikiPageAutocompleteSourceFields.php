<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class WikiPageAutocompleteSourceFields extends Base {

	public function apply() {
		$this->oLookup->addSourceField( [ 'namespace', 'namespace_text', 'prefixed_title', 'mtime', 'display_title' ] );
	}

	public function undo() {
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [
			Backend::QUERY_TYPE_AUTOCOMPLETE
		];
	}
}
