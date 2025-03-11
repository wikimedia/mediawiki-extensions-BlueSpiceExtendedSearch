<?php

namespace BS\ExtendedSearch\Source\Formatter;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class RepoFileFormatter extends FileFormatter {

	/**
	 * @inheritDoc
	 */
	public function format( &$resultData, $resultObject ): void {
		parent::format( $resultData, $resultObject );
		if ( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}
		$resultData['basename'] = $resultData['filename'];
	}

	/**
	 * @param array $result
	 * @return string
	 */
	protected function getActualImageUrl( $result ): string {
		$services = MediaWikiServices::getInstance();
		$file = $services->getRepoGroup()->findFile(
			Title::makeTitle( NS_FILE, $result['filename'] )
		);
		if ( !$file ) {
			return parent::getActualImageUrl( $result );
		}

		$hookContainer = $services->getHookContainer();
		$hookContainer->run( 'BSExtendedSearchRepoFileGetRepoFile', [
			&$file
		] );
		if ( $file ) {
			return $file->getCanonicalUrl();
		}

		return parent::getActualImageUrl( $result );
	}

	/**
	 * @param array &$results
	 * @param array $searchData
	 */
	public function formatAutocompleteResults( &$results, $searchData ): void {
		parent::formatAutocompleteResults( $results, $searchData );
		foreach ( $results as &$result ) {
			if ( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}
			$result['basename'] = $result['filename'];
			$result['image_uri'] = $this->getImage( $result );
			$result['namespace_text'] = '';
		}
	}
}
