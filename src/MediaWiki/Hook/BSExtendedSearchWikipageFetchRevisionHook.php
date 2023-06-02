<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

use MediaWiki\Revision\RevisionRecord;
use Title;

interface BSExtendedSearchWikipageFetchRevisionHook {
	/**
	 * @param Title $title
	 * @param RevisionRecord &$revision
	 *
	 * @return bool
	 */
	public function onBSExtendedSearchWikipageFetchRevision( Title $title, RevisionRecord &$revision );
}
