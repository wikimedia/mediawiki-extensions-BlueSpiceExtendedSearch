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

	public function scoreAutocompleteResults( &$results, $searchData ) {
		foreach( $results as &$result ) {
			if( $result['type'] !== $this->source->getTypeKey() ) {
				parent::scoreAutocompleteResults( $results, $searchData );
				continue;
			}

			if( $result['namespace'] === $searchData['namespace'] ) {
				if( strtolower( $result['basename'] ) == strtolower( $searchData['value'] ) ) {
					$result['score'] = 8;
				} else if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) !== false ) {
					if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) === 0 ) {
						$result['score'] = 7;
					} else {
						$result['score'] = 6;
					}
				} else {
					$result['score'] = 2;
				}
			} else if( $result['namespace'] !== $searchData['namespace'] || $searchData['namespace'] === 0 ) {
				if( strtolower( $result['basename'] ) == strtolower( $searchData['value'] ) ) {
					$result['score'] = 5;
				} else if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) !== false ) {
					if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) === 0 ) {
						$result['score'] = 4;
					} else {
						$result['score'] = 3;
					}
				}
			}

			$result['is_scored'] = true;
		}
	}
}

