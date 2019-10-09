<?php

namespace BS\ExtendedSearch\Param\Parser;

class SearchResultTypeParser extends \ValueParsers\StringValueParser {

	/**
	 * @param string $value
	 * @return mixed|string
	 */
	protected function stringParse( $value ) {
		if ( is_string( $value ) ) {
			$value = strtolower( $value );
		}

		return $value;
	}

	/**
	 * This method is required by the interface. Since the base class does not implement it
	 * and SearchResultTypeParser does not track any arrors, we simply return an empty array
	 * @return array
	 */
	protected function getErrors() {
		return [];
	}

}
