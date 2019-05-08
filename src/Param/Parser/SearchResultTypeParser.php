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

}
