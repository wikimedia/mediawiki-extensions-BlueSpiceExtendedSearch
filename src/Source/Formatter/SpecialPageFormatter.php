<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\Source\Formatter\Base;

class SpecialPageFormatter extends Base {
	public function format( &$result, $resultObject ) {
		if( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}

		parent::format( $result, $resultObject );
	}
}

