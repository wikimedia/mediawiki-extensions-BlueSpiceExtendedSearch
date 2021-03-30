<?php

namespace BS\ExtendedSearch\Source\Formatter;

use Hooks;
use Title;

class RepoFileFormatter extends FileFormatter {

	/**
	 * @inheritDoc
	 */
	public function format( &$result, $resultObject ) {
		parent::format( $result, $resultObject );
		$result['basename'] = $result['filename'];
	}

	/**
	 * @param array $result
	 * @return string
	 */
	protected function getActualImageUrl( $result ) {
		$file = \RepoGroup::singleton()->findFile(
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
}
