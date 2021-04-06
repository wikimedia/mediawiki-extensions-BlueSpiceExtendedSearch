<?php

namespace BS\ExtendedSearch\Source\Formatter;

class FileFormatter extends Base {

	/**
	 *
	 * @param array &$result
	 * @param \Elastica\Result $resultObject
	 */
	public function format( &$result, $resultObject ) {
		if ( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}

		$result['image_uri'] = $this->getImage( $result );
		parent::format( $result, $resultObject );
		$result['highlight'] = $this->getHighlight( $resultObject );
	}

	/**
	 *
	 * @param array $result
	 * @return string
	 */
	protected function getImage( $result ) {
		$mimeType = $result['mime_type'];
		if ( strpos( $mimeType, 'image' ) === 0 ) {
			// Show actual image
			return $this->getActualImageUrl( $result );
		}

		$extension = strtolower( $result['extension'] );
		$fileIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchIcons' );

		$scriptPath = $this->getContext()->getConfig()->get( 'ScriptPath' );
		if ( isset( $fileIcons[$extension] ) ) {
			return $scriptPath . $fileIcons[$extension];
		}
		return $scriptPath . $fileIcons['default'];
	}

	/**
	 * @param array $result
	 * @return string
	 */
	protected function getActualImageUrl( $result ) {
		return $result['uri'];
	}

	/**
	 *
	 * @param array $defaultResultStructure
	 * @return string
	 */
	public function getResultStructure( $defaultResultStructure = [] ) {
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
	 * @param \Elastica\Result $resultObject
	 * @return string
	 */
	protected function getHighlight( $resultObject ) {
		$highlights = $resultObject->getHighlights();
		if ( isset( $highlights['attachment.content'] ) ) {
			return implode( ' ', $highlights['attachment.content'] );
		}
		return '';
	}
}
