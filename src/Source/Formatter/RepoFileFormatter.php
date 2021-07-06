<?php

namespace BS\ExtendedSearch\Source\Formatter;

use Hooks;
use MediaWiki\MediaWikiServices;
use Title;

class RepoFileFormatter extends FileFormatter {

	/**
	 * @inheritDoc
	 */
	public function format( &$result, $resultObject ) {
		parent::format( $result, $resultObject );
		if ( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}
		$result['basename'] = $result['filename'];
	}

	/**
	 * @param array $result
	 * @return string
	 */
	protected function getActualImageUrl( $result ) {
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile(
			Title::makeTitle( NS_FILE, $result['filename'] )
		);
		if ( !$file ) {
			return '';
		}

		Hooks::run( 'BSExtendedSearchRepoFileGetRepoFile', [
			&$file
		] );
		if ( $file ) {
			return $file->getCanonicalUrl();
		}

		return '';
	}

	/**
	 * @param array &$results
	 * @param array $searchData
	 */
	public function formatAutocompleteResults( &$results, $searchData ) {
		parent::formatAutocompleteResults( $results, $searchData );
		foreach ( $results as &$result ) {
			$result['basename'] = $result['filename'];
			$result['image_uri'] = $this->getActualImageUrl( $result );
		}
	}
}
