<?php

namespace BS\ExtendedSearch;

use MediaWiki\Context\IContextSource;

interface ILookupModifierProvider {

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 *
	 * @return array
	 */
	public function getLookupModifiers( Lookup $lookup, IContextSource $context ): array;
}
