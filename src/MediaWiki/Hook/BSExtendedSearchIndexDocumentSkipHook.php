<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

use BS\ExtendedSearch\Source\Job\UpdateJob;

interface BSExtendedSearchIndexDocumentSkipHook {
	/**
	 * @param UpdateJob $updateJob
	 * @param bool &$skip
	 * @return bool
	 */
	public function onBSExtendedSearchIndexDocumentSkip( UpdateJob $updateJob, bool &$skip );
}
