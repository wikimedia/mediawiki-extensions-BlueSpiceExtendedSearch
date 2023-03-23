<?php

namespace BS\ExtendedSearch\Param\Validator;

use MediaWiki\MediaWikiServices;
use ValueValidators\PackagePrivate\ValueValidatorBase;

class SearchResultTypeValidator extends ValueValidatorBase {

	/**
	 * Makes sure each given type is a valid search type
	 *
	 * @param string $value
	 */
	public function doValidation( $value ) {
		if ( is_string( $value ) == false ) {
			$this->addErrorMessage(
				wfMessage(
					'bs-extendedsearch-tagsearch-parser-error-invalid-search-type',
					$value
				)->plain()
			);
		}

		$sourceTypeKeys = $this->getSourceTypeKeys();
		if ( in_array( $value, $sourceTypeKeys ) == false ) {
			$this->addErrorMessage(
				wfMessage(
					'bs-extendedsearch-tagsearch-parser-error-invalid-search-type',
					$value
				)->plain()
			);
		}
	}

	/**
	 * Gets all types of all sources
	 *
	 * @return array
	 */
	protected function getSourceTypeKeys() {
		$sourceTypeKeys = [];
		$backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
		$sources = $backend->getSources();
		foreach ( $sources as $source ) {
			$sourceTypeKeys[] = $source->getTypeKey();
		}

		return $sourceTypeKeys;
	}
}
