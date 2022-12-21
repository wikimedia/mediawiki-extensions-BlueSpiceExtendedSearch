<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

use BS\ExtendedSearch\Source\Base as Source;

interface BSExtendedSearchBeforeCreateIndexHook {
	/**
	 *
	 * @param Source $source
	 * @param array &$indexSettings
	 * @param array &$propertyMapping
	 * @return void
	 */
   public function onBSExtendedSearchBeforeCreateIndex( $source, &$indexSettings, &$propertyMapping );
}
