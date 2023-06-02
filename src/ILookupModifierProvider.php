<?php

namespace BS\ExtendedSearch;

use IContextSource;

interface ILookupModifierProvider {

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 *
	 * @return array
	 */
	public function getLookupModifiers( Lookup $lookup, IContextSource $context ): array;
}
