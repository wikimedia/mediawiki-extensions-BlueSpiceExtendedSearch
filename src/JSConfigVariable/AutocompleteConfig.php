<?php

namespace BS\ExtendedSearch\JSConfigVariable;

use BlueSpice\JSConfigVariable;
use ExtensionRegistry;

class AutocompleteConfig extends JSConfigVariable {

	/**
	 * @inheritDoc
	 */
	public function getValue() {
		return ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );
	}
}
