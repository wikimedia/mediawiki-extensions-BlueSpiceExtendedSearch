<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class WikiPageAutocompleteSourceFields extends LookupModifier {

	public function apply() {
		$this->lookup->addSourceField( [
			'namespace', 'namespace_text', 'prefixed_title', 'mtime',
			'display_title', 'page_id', 'is_redirect', 'redirects_to'
		] );
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
