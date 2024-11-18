<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

use BS\ExtendedSearch\Lookup;

interface BSExtendedSearchGetLookupModifiers {

	/**
	 * @param array &$modifiers
	 * @param Lookup $lookup
	 * @param string $type
	 * @return mixed
	 */
	public function onBSExtendedSearchGetLookupModifiers( array &$modifiers, Lookup $lookup, string $type );
}
