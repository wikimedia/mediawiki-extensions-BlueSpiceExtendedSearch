<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\ISearchSource;

interface IMappingModifier {
	/**
	 * @param ISearchSource $source
	 * @param array &$indexSettings
	 * @param array &$propertyMapping
	 *
	 * @return mixed
	 */
	public function modifyMapping( ISearchSource $source, array &$indexSettings, array &$propertyMapping ): void;
}
