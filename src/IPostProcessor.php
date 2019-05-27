<?php

namespace BS\ExtendedSearch;

use Elastica\Result;

interface IPostProcessor {

	/**
	 * @param PostProcessor $base
	 * @return IPostProcessor
	 */
	public static function factory( PostProcessor $base );

	/**
	 * @param Result $result
	 * @param Lookup $lookup
	 * @return void
	 */
	public function process( Result &$result, Lookup $lookup );
}
