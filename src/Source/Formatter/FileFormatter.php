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
		$result['highlight'] = $this->getHighlight( $resultObject );
	}

	protected function getImage( $result ) {
		$mimeType = $result['mime_type'];
		if( strpos( $mimeType, 'image' ) === 0 ) {
			//Show actual image
			return $result['uri'];
		}

		$extension = $result['extension'];
		$fileIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchIcons' );

		$scriptPath = $this->getContext()->getConfig()->get( 'ScriptPath' );
		if( isset( $fileIcons[$extension] ) ) {
			return  $scriptPath . $fileIcons[$extension];
		}
		return $scriptPath . $fileIcons['default'];
	}

	public function getResultStructure ( $defaultResultStructure = [] ) {
		$resultStructure = $defaultResultStructure;
		$resultStructure['imageUri'] = "image_uri";
		$resultStructure['highlight'] = "highlight";

		//All fields under "featured" key will only appear is result is featured
		$resultStructure['featured']['imageUri'] = "image_uri";

		return $resultStructure;
	}

	protected function getHighlight( $resultObject ) {
		$highlights = $resultObject->getHighlights();
		if( isset( $highlights['attachment.content'] ) ) {
			return implode( ' ', $highlights['attachment.content'] );
		}
		return '';
	}
}
