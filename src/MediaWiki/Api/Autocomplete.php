<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use BS\ExtendedSearch\Backend;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class Autocomplete extends ApiBase {
	/**
	 *
	 * @var \BS\ExtendedSearch\Lookup
	 */
	protected $lookup = null;

	/**
	 *
	 * @var string Backend name
	 */
	protected $backend = '';

	/**
	 *
	 * @var array
	 */
	protected $searchData;

	/**
	 *
	 * @var array
	 */
	protected $secondaryRequestData;

	public function execute() {
		$this->readInParameters();
		$this->lookUpResults();
		$this->returnResults();
	}

	/**
	 *
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'q' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-q',
			],
			'backend' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 'local',
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-backend',
			],
			'searchData' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-search-data',
			],
			'secondaryRequestData' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-secondary-request-data',
			]
		];
	}

	/**
	 *
	 * @param string $paramName Parameter name
	 * @param array|mixed $paramSettings Default value or an array of settings
	 *  using PARAM_* constants.
	 * @param bool $parseLimit Whether to parse and validate 'limit' parameters
	 * @return mixed Parameter value
	 */
	protected function getParameterFromSettings( $paramName, $paramSettings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $paramName, $paramSettings, $parseLimit );
		if ( $paramName === 'q' ) {
			$decodedValue = FormatJson::decode( $value, true );
			if ( is_array( $decodedValue ) ) {
				return new \BS\ExtendedSearch\Lookup( $decodedValue );
			}
		}
		if ( $paramName === 'searchData' ) {
			return FormatJson::decode( $value, true );
		}

		if ( $paramName === 'secondaryRequestData' ) {
			return FormatJson::decode( $value, true );
		}

		return $value;
	}

	protected function readInParameters() {
		$this->lookup = $this->getParameter( 'q' );
		$this->backend = $this->getParameter( 'backend' );
		$this->searchData = $this->getParameter( 'searchData' );
		$this->secondaryRequestData = $this->getParameter( 'secondaryRequestData' );
	}

	/**
	 *
	 * @var array
	 */
	protected $suggestions;

	protected function lookUpResults() {
		/** @var Backend $backend */
		$backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
		if ( $this->secondaryRequestData ) {
			$this->suggestions = $backend->runAutocompleteSecondaryLookup(
				$this->lookup,
				$this->searchData,
				$this->secondaryRequestData
			);
			return;
		}
		$this->suggestions = $backend->runAutocompleteLookup( $this->lookup, $this->searchData );
	}

	/** @var ApiResult */
	protected $oResult;

	protected function returnResults() {
		$oResult = $this->getResult();
		$oResult->addValue( null, 'suggestions', $this->suggestions );
	}
}
