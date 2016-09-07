<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class Query extends \ApiBase {
	public function execute() {
		$oResult = $this->getResult();
		$sQuery = $this->getParameter( 'q' );

		$oBackend = \BS\ExtendedSearch\Backend::instance( 'local' ); //TODO: Use parameter for selection of "backend" and "source"
		$oResultSet = $oBackend->search( $sQuery, $this->getUser() );

		$oResult->addValue( null , 'results', $oBackend->formatResults( $oResultSet->getResults() ) );
		$oResult->addValue( null , 'total', $oResultSet->getTotalHits() );
		//Future:
		//$oResult->addValue( null , 'aggregations', $oResultSet->getAggregations() );
		//$oResult->addValue( null , 'suggests', $oResultSet->getSuggests() );
	}

	protected function getAllowedParams() {
		return [
			'q' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-q',
			]
		];
	}
}