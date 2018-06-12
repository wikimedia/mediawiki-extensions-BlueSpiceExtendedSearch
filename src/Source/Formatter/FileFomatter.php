<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\Source\Formatter\Base;

class FileFormatter extends Base {
	public function format( &$result, $resultObject ) {
		if( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}

		parent::format( $result, $resultObject );

		$result['image_uri'] = $this->getImage( $result );
	}

	protected function getImage( $result ) {
		$mimeType = $result['mime_type'];
		if( strpos( $mimeType, 'image' ) === 0 ) {
			//Show actual image
			return $result['uri'];
		}

		$extension = $result['extension'];

		//Is there a centralized place to get file icons, so
		//that those dont have to come with this extension?
		$fileIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchFileIcons' );

		if( isset( $fileIcons[$extension] ) ) {
			return $fileIcons[$extension];
		}
		return $fileIcons['default'];
	}

	public function getResultStructure ( $defaultResultStructure = [] ) {
		$resultStructure = $defaultResultStructure;
		$resultStructure['imageUri'] = "image_uri";

		//All fields under "featured" key will only appear is result is featured
		$resultStructure['featured']['imageUri'] = "image_uri";

		return $resultStructure;
	}
}
