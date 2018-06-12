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

	public function formatAutocompleteResults( &$results, $searchData ) {
		parent::formatAutocompleteResults( $results, $searchData );

		foreach( $results as &$result ) {
			if( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			$origBasename = $result['basename'];

			if( -1 != $searchData['namespace'] ) {
				$result['basename'] = $result['prefixed_title'];
			}

			$title = \Title::makeTitle( NS_SPECIAL, $origBasename );
			if( $title instanceof \Title ) {
				$result['pageAnchor'] = $this->linkRenderer->makeLink( $title, $result['basename'] );
			}
		}
	}
}

