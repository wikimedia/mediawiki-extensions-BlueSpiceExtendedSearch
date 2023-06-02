<?php

namespace BS\ExtendedSearch;

interface ISearchResultFormatter {

	/**
	 * Sets current instance of Lookup object that the
	 * result being formatted
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 */
	public function setLookup( $lookup ): void;

	/**
	 * Returns structure of the result for each source
	 * It allows sources to modify default result structure
	 *
	 * @param array $defaultResultStructure
	 * @return array
	 */
	public function getResultStructure( $defaultResultStructure = [] ): array;

	/**
	 * Allows sources to modify data returned by ES,
	 * before it goes to the client-side
	 *
	 * @param array &$resultDataData
	 * @param SearchResult $resultObject
	 */
	public function format( &$resultDataData, SearchResult $resultObject ): void;

	/**
	 * Allows sources to modify results of autocomplete query
	 *
	 * @param array &$results
	 * @param array $searchData
	 */
	public function formatAutocompleteResults( &$results, $searchData ): void;

	/**
	 * Allows sources to change ranking of the autocomplete query results
	 * Exact matches are TOP, matches containing search term are NORMAL,
	 * and matches not containing search term (fuzzy) are SECONDARY
	 *
	 * Ranking controls where result will be shown( which part of AC popup )
	 *
	 * @param array &$results
	 * @param array $searchData
	 */
	public function rankAutocompleteResults( &$results, $searchData ): void;

	/**
	 * Allows sources to modify filterCfg if needed
	 *
	 * @param array &$aggs
	 * @param array &$filterCfg
	 * @param bool $fieldsWithANDEnabled
	 */
	public function formatFilters( &$aggs, &$filterCfg, $fieldsWithANDEnabled = false ): void;
}
