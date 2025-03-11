<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\SearchResult;
use MediaWiki\Registration\ExtensionRegistry;

class FileFormatter extends Base {

	/**
	 *
	 * @param array &$resultData
	 * @param SearchResult $resultObject
	 */
	public function format( &$resultData, $resultObject ): void {
		if ( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}

		$resultData['image_uri'] = $this->getImage( $resultData );
		parent::format( $resultData, $resultObject );
		$resultData['highlight'] = $this->getHighlight( $resultObject );
	}

	/**
	 *
	 * @param array $resultData
	 * @return string
	 */
	protected function getImage( $resultData ) {
		$mimeType = $resultData['mime_type'];
		if ( str_starts_with( $mimeType, 'image' ) ) {
			// Show actual image
			return $this->getActualImageUrl( $resultData );
		}

		$extension = strtolower( $resultData['extension'] );
		$fileIcons = ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchIcons' );

		$scriptPath = $this->getContext()->getConfig()->get( 'ScriptPath' );
		if ( isset( $fileIcons[$extension] ) ) {
			return $scriptPath . $fileIcons[$extension];
		}
		return $scriptPath . $fileIcons['default'];
	}

	/**
	 * @param array $resultData
	 * @return string
	 */
	protected function getActualImageUrl( $resultData ): string {
		return $resultData['uri'];
	}

	/**
	 *
	 * @param array $defaultResultStructure
	 *
	 * @return array
	 */
	public function getResultStructure( $defaultResultStructure = [] ): array {
		$resultStructure = $defaultResultStructure;
		$resultStructure['imageUri'] = "image_uri";
		$resultStructure['highlight'] = "highlight";
		$resultStructure['secondaryResults']['top']['items'][] = [
			"name" => "file_usage"
		];

		// All fields under "featured" key will only appear is result is featured
		$resultStructure['featured']['imageUri'] = "image_uri";

		return $resultStructure;
	}

	/**
	 *
	 * @param SearchResult $resultObject
	 * @return string
	 */
	protected function getHighlight( $resultObject ) {
		$highlights = $resultObject->getParam( 'highlight' );
		if ( isset( $highlights['attachment.content'] ) ) {
			return implode( ' ', $highlights['attachment.content'] );
		}
		return '';
	}
}
