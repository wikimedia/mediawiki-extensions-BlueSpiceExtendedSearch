<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

use MediaWiki\FileRepo\File\File;

interface BSExtendedSearchRepoFileGetFileHook {
	/**
	 * @param File &$file
	 *
	 * @return bool
	 */
	public function onBSExtendedSearchRepoFileGetFile( File &$file );
}
