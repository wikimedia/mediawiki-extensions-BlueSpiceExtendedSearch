<?php

namespace BS\ExtendedSearch\Source\Formatter;

use Hooks;

class RepoFileFormatter extends FileFormatter {

	/**
	 * @param array $result
	 * @return string
	 */
	protected function getActualImageUrl( $result ) {
		$file = \RepoGroup::singleton()->findFile(
			\Title::makeTitle( NS_FILE, $result['basename'] )
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
