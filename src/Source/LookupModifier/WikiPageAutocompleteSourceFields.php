<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageAutocompleteSourceFields extends Base {

	public function apply() {
		$this->oLookup->addSourceField( [ 'namespace', 'namespace_text', 'prefixed_title', 'mtime', 'display_title' ] );
	}

	public function undo() {
	}

}
