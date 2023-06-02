<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\ISearchSource;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\SearchResult;

interface IFormattingModifier {
	/**
	 * @param array &$result
	 * @param SearchResult $resultObject
	 * @param ISearchSource $source
	 * @param Lookup $lookup
	 *
	 * @return mixed
	 */
	public function formatFulltextResult(
		array &$result, SearchResult $resultObject, ISearchSource $source, Lookup $lookup
	): void;

	/**
	 * @param array &$results
	 * @param array $searchData
	 *
	 * @return void
	 */
	public function formatAutocompleteResults( array &$results, array $searchData ): void;

	/**
	 * @param array &$resultStructure
	 * @param ISearchSource $source
	 *
	 * @return void
	 */
	public function modifyResultStructure( array &$resultStructure, ISearchSource $source ): void;
}
