<?php

namespace BS\ExtendedSearch\ConfigDefinition;

class ExternalFilePaths extends \BlueSpice\ConfigDefinition {
	const EXTENSION_EXTENDED_SEARCH = 'BlueSpiceExtendedSearch';

	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_SEARCH . '/' . static::EXTENSION_EXTENDED_SEARCH,
			static::MAIN_PATH_EXTENSION . '/' . static::EXTENSION_EXTENDED_SEARCH . '/' . static::FEATURE_SEARCH,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/' . static::EXTENSION_EXTENDED_SEARCH,
		];
	}

	public function getLabelMessageKey() {
		return 'bs-extendedsearch-pref-external-file-paths';
	}

	public function getVariableName() {
		return 'bsg' . $this->getName();
	}

	public function getHtmlFormField() {
		return new \BlueSpice\Html\FormField\KeyValueField( $this->makeFormFieldParams() );
	}

	protected function makeFormFieldParams() {
		return array_merge( parent::makeFormFieldParams(), [
			'allowAdditions' => true,
			'valueRequired' => false,
			'labelsOnlyOnFirst' => true,
			'keyLabel' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-path' )->plain(),
			'valueLabel' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-url-prefix' )->plain(),
			'keyHelp' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-path-help' )->plain(),
			'valueHelp' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-url-help' )->plain()
		] );
	}
}
