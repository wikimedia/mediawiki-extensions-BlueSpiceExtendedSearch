<?php

namespace BS\ExtendedSearch\JSConfigVariable;

use BlueSpice\JSConfigVariable;
use ExtensionRegistry;

class SourceIcons extends JSConfigVariable {

	/**
	 * @inheritDoc
	 */
	public function getValue() {
		return ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSourceIcons' );
	}
}
