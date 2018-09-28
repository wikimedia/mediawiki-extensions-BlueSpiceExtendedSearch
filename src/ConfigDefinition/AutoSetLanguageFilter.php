<?php

namespace BS\ExtendedSearch\ConfigDefinition;

class AutoSetLanguageFilter extends \BlueSpice\ConfigDefinition\BooleanSetting {
	const EXTENSION_EXTENDED_SEARCH = 'BlueSpiceExtendedSearch';

	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_SEARCH . '/' . static::EXTENSION_EXTENDED_SEARCH,
			static::MAIN_PATH_EXTENSION . '/' . static::EXTENSION_EXTENDED_SEARCH . '/' . static::FEATURE_SEARCH,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/' . static::EXTENSION_EXTENDED_SEARCH,
		];
	}

	public function getLabelMessageKey() {
		return 'bs-extendedsearch-pref-auto-set-lang-filter';
	}

}
