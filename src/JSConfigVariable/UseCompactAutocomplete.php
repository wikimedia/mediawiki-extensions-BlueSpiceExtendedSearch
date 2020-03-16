<?php

namespace BS\ExtendedSearch\JSConfigVariable;

use BlueSpice\JSConfigVariable;

class UseCompactAutocomplete extends JSConfigVariable {

	/**
	 * @inheritDoc
	 */
	public function getValue() {
		return $this->getConfig()->get( 'ESCompactAutocomplete' );
	}
}
