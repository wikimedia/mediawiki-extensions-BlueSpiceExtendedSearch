<?php

namespace BS\ExtendedSearch\ConfigDefinition;

class CompactAutocomplete extends \BlueSpice\ConfigDefinition\BooleanSetting {
	public const EXTENSION_EXTENDED_SEARCH = 'BlueSpiceExtendedSearch';

	/**
	 *
	 * @return string[]
	 */
	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_SEARCH . '/' . static::EXTENSION_EXTENDED_SEARCH,
			static::MAIN_PATH_EXTENSION . '/' . static::EXTENSION_EXTENDED_SEARCH . '/' . static::FEATURE_SEARCH,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/' . static::EXTENSION_EXTENDED_SEARCH,
		];
	}

	/**
	 *
	 * @return string
	 */
	public function getLabelMessageKey() {
		return 'bs-extendedsearch-pref-compact-ac-paths';
	}

	/**
	 *
	 * @return string
	 */
	public function getHelpMessageKey() {
		return 'bs-extendedsearch-pref-compact-ac-paths-help';
	}
}
