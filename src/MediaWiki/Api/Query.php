<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class Query extends \ApiBase {
	public function execute() {
		$this->readInParameters();
		$this->lookUpResults();
		$this->returnResults();
	}

	protected function getAllowedParams() {
		return [
			'q' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-q',
			],
			'params' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false,
				\ApiBase::PARAM_DFLT => '{}',
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-params',
			],
			'backend' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false,
				\ApiBase::PARAM_DFLT => 'local',
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-backend',
			]
		];
	}

	protected function getParameterFromSettings( $paramName, $paramSettings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $paramName, $paramSettings, $parseLimit );
		if ( $paramName === 'q' ) {
			$decodedValue = \FormatJson::decode( $value, true );
			$oLookup = new \BS\ExtendedSearch\Lookup();
			if( is_array( $decodedValue ) ) {
				$oLookup = new \BS\ExtendedSearch\Lookup( $decodedValue );
			}
			else {
				$oLookup->setSimpleQueryString( $value );
			}

			return $oLookup;
		}
		return $value;
	}

	/**
	 *
	 * @var \BS\ExtendedSearch\Lookup
	 */
	protected $oLookup = null;
	protected $aParams = [];
	protected $sBackend = '';

	protected function readInParameters() {
		$this->oLookup = $this->getParameter( 'q' );
		$this->aParams = $this->getParameter( 'params' );
		$this->sBackend = $this->getParameter( 'backend' );
	}

	/**
	 *
	 * @var \Elastica\ResultSet
	 */
	protected $oResults = null;
	protected function lookUpResults() {
		$oBackend = \BS\ExtendedSearch\Backend::instance( $this->sBackend );
		$this->oResultSet = $oBackend->runLookup( $this->oLookup );
	}

	protected function returnResults() {
		$oResult = $this->getResult();

		$oResult->addValue( null , 'results', $this->formatResults() );
		$oResult->addValue( null , 'total', $this->formatTotal() );
		$oResult->addValue( null , 'aggregations', $this->formatAggregations() );
		$oResult->addValue( null , 'suggests', $this->formatSuggests() );
	}

	protected function formatResults() {
		return [];
		return $this->oResultSet->getResults();
	}

	protected function formatTotal() {
		return 0;
		return $this->oResultSet->getTotalHits();
	}

	protected function formatAggregations() {
		return [];
		return $this->oResultSet->getAggregations();
	}

	protected function formatSuggests() {
		return [];
		return $this->oResultSet->getSuggests();
	}
}