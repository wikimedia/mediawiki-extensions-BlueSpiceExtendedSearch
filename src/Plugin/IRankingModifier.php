<?php

namespace BS\ExtendedSearch\Plugin;

interface IRankingModifier {

	/**
	 * @param array &$results
	 * @param array $searchData
	 * @return void
	 */
	public function rankAutocompleteResults( array &$results, array $searchData ): void;
}
