<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\SearchResult;

interface IPostProcessor {

	/**
	 * @param SearchResult &$result
	 * @param Lookup $lookup
	 * @return void
	 */
	public function process( SearchResult &$result, Lookup $lookup );
}
