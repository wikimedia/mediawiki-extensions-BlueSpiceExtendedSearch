<?php

namespace BS\ExtendedSearch;

use BlueSpice\ExtensionAttributeBasedRegistry;

class SourceRegistry extends ExtensionAttributeBasedRegistry {
	public function __construct() {
		parent::__construct( 'BlueSpiceExtendedSearchSources' );
	}

	public function getAllSources() {
		$sources = [];
		foreach( $this->getAllKeys() as $key ) {
			$sources[$key] = $this->getSourceByKey( $key );
		}

		return $sources;
	}

	public function getSourceByKey( $key ) {
		$registry = $this->extensionRegistry->getAttribute( $this->attribName );
		if( !isset( $registry[$key] ) ) {
			return false;
		}

		$rawSource = $registry[$key];

		$class = $rawSource['class'];
		if( is_array( $class ) ) {
			$class = end( $class );
		}

		$source = [
			'class' => $class
		];

		if( isset( $rawSource['args'] ) ) {
			$args = $rawSource['args'];
			$mergedArgs = [];
			foreach( $args as $argsMember ) {
				$mergedArgs = array_merge_recursive( $mergedArgs, $argsMember );
			}
			$source['args'] = [ $mergedArgs ];
		}

		return $source;
	}
}
