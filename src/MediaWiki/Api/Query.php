<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class Query extends ApiBase {
	/** @var string */
	protected $searchTerm;
	/** @var array */
	protected $pageCreateData = [];

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
			'searchTerm' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-search-term'
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

			$oLookup = new \BS\ExtendedSearch\Lookup();
			if ( is_array( $decodedValue ) ) {
				$oLookup = new \BS\ExtendedSearch\Lookup( $decodedValue );
			} else {
				$oLookup->setQueryString( $value );
			}

			return $oLookup;
		}
		return $value;
	}

	/**
	 *
	 * @var \BS\ExtendedSearch\Lookup|null
	 */
	protected $oLookup = null;
	/** @var string */
	protected $sBackend = '';

	protected function readInParameters() {
		$this->oLookup = $this->getParameter( 'q' );
		$this->sBackend = $this->getParameter( 'backend' );
		$this->searchTerm = $this->getParameter( 'searchTerm' );
	}

	/**
	 *
	 * @var stdClass
	 */
	protected $resultSet;

	protected function lookUpResults() {
		$oBackend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
		$this->resultSet = $oBackend->runLookup( $this->oLookup );
	}

	/** @var ApiResult */
	protected $oResult;

	protected function returnResults() {
		$oResult = $this->getResult();

		if ( isset( $this->resultSet->exception ) ) {
			// Search query caused an exception - usually malformed query
			$oResult->addValue( null, 'exception', 1 );
			$oResult->addValue( null, 'exceptionType', $this->resultSet->exceptionType );
			return;
		}

		$oResult->addValue( null, 'results', $this->resultSet->results );
		$oResult->addValue( null, 'total', $this->resultSet->total );
		$oResult->addValue( null, 'filters', $this->resultSet->filters );
		$oResult->addValue( null, 'spellcheck', $this->resultSet->spellcheck );
		$oResult->addValue( null, 'lookup', FormatJson::encode( $this->oLookup ) );
		$oResult->addValue( null, 'total_approximated', $this->resultSet->total_approximated );
		$oResult->addValue( null, 'search_after', $this->resultSet->search_after );
	}
}
