<?php

namespace BS\ExtendedSearch\ConfigDefinition;

use BlueSpice\ConfigDefinition;
use BlueSpice\Html\FormField\KeyValueField;
use Exception;

class ExternalFilePathsExcludes extends ConfigDefinition {
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
		return 'bs-extendedsearch-pref-external-file-paths-excludes';
	}

	/**
	 *
	 * @return KeyValueField
	 */
	public function getHtmlFormField() {
		return new KeyValueField( $this->makeFormFieldParams() );
	}

	/**
	 *
	 * @return array
	 */
	protected function makeFormFieldParams() {
		return array_merge( parent::makeFormFieldParams(), [
			'allowAdditions' => true,
			'valueRequired' => true,
			'labelsOnlyOnFirst' => true,
			'keyLabel' => $this->msg(
				'bs-extendedsearch-pref-external-file-paths-path'
			)->text(),
			'valueLabel' => $this->msg(
				'bs-extendedsearch-pref-external-file-paths-excludes-exclude'
			)->text(),
			'keyHelp' => $this->msg(
				'bs-extendedsearch-pref-external-file-paths-path-help'
			)->text(),
			'valueHelp' => $this->msg(
				'bs-extendedsearch-pref-external-file-paths-excludes-exclude-help'
			)->text(),
			'validation-callback' => function ( $value, $alldata, $parent ) {
				foreach ( $value as $key => $pattern ) {
					try {
						$valid = preg_match( $pattern, null );
					} catch ( Exception $e ) {
						return $e->getMessage();
					}
					if ( $valid !== false ) {
						return true;
					}
					return $this->msg(
						'bs-extendedsearch-pref-external-file-paths-excludes-invalid'
					);
				}
				return true;
			},
		] );
	}
}
