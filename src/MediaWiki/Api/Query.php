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
	protected $sBackend = '';

	protected function readInParameters() {
		$this->oLookup = $this->getParameter( 'q' );
		$this->sBackend = $this->getParameter( 'backend' );
	}

	/**
	 *
	 * @var stdClass $resultSet
	 */
	protected $resultSet;
	protected function lookUpResults() {
		$oBackend = \BS\ExtendedSearch\Backend::instance( $this->sBackend );
		$this->resultSet = $oBackend->runLookup( $this->oLookup );
	}

	protected $oResult;
	protected function returnResults() {
		$oResult = $this->getResult();

		$oResult->addValue( null , 'results', $this->resultSet->results );
		$oResult->addValue( null , 'total', $this->resultSet->total );
		$oResult->addValue( null , 'aggregations', $this->resultSet->aggregations );
		$oResult->addValue( null , 'suggests', $this->resultSet->suggests );
	}

	//Make some other class for this result decoration
	protected function formatResults() {
		$results = [];

		$oBackend = \BS\ExtendedSearch\Backend::instance( $this->sBackend );

		foreach( $this->oResultSet->getResults() as $resultObject ) {
			$result = $resultObject->getData();
			foreach( $oBackend->getSources() as $sSourceKey => $oSource ) {
				$oSource->getFormatter()->format( $result, $resultObject );
			}
			$results[] = $result;
		}
		return $results;
	}

	protected function formatTotal() {
		return $this->oResultSet->getTotalHits();
	}

	protected function formatAggregations() {
		return $this->oResultSet->getAggregations();
	}

	protected function formatSuggests() {
		return $this->oResultSet->getSuggests();
	}
}