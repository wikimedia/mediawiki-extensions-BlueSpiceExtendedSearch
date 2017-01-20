<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class Query extends \ApiBase {
	public function execute() {
		$oResult = $this->getResult();
		$sQuery = $this->getParameter( 'q' );
		$aParams = $this->getParameter( 'params' );
		$sBackend = $this->getParameter( 'backend' );

		$oBackend = \BS\ExtendedSearch\Backend::instance( $sBackend );
		$oLookup = new \BS\ExtendedSearch\Lookup( $oBackend, $this->getContext() );
		$oResultSet = $oLookup->run( $sQuery, $aParams );

		$oResult->addValue( null , 'results', $oResultSet->getResults() );
		$oResult->addValue( null , 'total', $oResultSet->getTotalHits() );
		$oResult->addValue( null , 'aggregations', $oResultSet->getAggregations() );
		//Future:
		//$oResult->addValue( null , 'suggests', $oResultSet->getSuggests() );
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
		if ( $paramName === 'params' ) {
			$value = \FormatJson::decode( $value, true );
			if( empty( $value ) ) {
				return [];
			}
		}
		return $value;
	}
}