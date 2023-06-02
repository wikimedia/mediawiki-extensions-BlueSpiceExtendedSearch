<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\ISearchSource;

interface IFilterModifier {
	/**
	 * @param array &$aggregations
	 * @param array &$filterCfg
	 * @param array $fieldsWithANDEnabled
	 * @param ISearchSource $source
	 *
	 * @return mixed
	 */
	public function modifyFilters(
		array &$aggregations, array &$filterCfg, array $fieldsWithANDEnabled, ISearchSource $source
	): void;
}
