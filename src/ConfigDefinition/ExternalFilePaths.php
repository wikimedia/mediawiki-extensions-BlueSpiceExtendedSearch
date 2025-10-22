<?php

namespace BS\ExtendedSearch\ConfigDefinition;

class ExternalFilePaths extends \BlueSpice\ConfigDefinition {
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
		return 'bs-extendedsearch-pref-external-file-paths';
	}

	/**
	 *
	 * @return string
	 */
	public function getVariableName() {
		return 'bsg' . $this->getName();
	}

	/**
	 *
	 * @return \BlueSpice\Html\FormField\KeyValueField
	 */
	public function getHtmlFormField() {
		return new \BlueSpice\Html\FormField\KeyValueField( $this->makeFormFieldParams() );
	}

	/**
	 *
	 * @return array
	 */
	protected function makeFormFieldParams() {
		return array_merge( parent::makeFormFieldParams(), [
			'allowAdditions' => true,
			'valueRequired' => false,
			'labelsOnlyOnFirst' => true,
			'keyLabel' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-path' )->text(),
			'valueLabel' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-url-prefix' )->text(),
			'keyHelp' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-path-help' )->text(),
			'valueHelp' => wfMessage( 'bs-extendedsearch-pref-external-file-paths-url-help' )->text()
		] );
	}

	/**
	 *
	 * @return string
	 */
	public function getHelpMessageKey() {
		return 'bs-extendedsearch-pref-externalfilepaths-help';
	}
}
