<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class ResultRelevance extends \ApiBase {
	public function execute() {
		$this->readInParameters();
		$this->applyRelevanceChange();
		$this->returnResults();
	}

	protected function getAllowedParams() {
		return [
			'relevanceData' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-relevance-data',
			]
		];
	}

	protected function getParameterFromSettings( $paramName, $paramSettings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $paramName, $paramSettings, $parseLimit );
		if ( $paramName === 'relevanceData' ) {
			$decodedValue = \FormatJson::decode( $value, true );
			if( is_array( $decodedValue ) ) {
				return $this->makeResultRelevanceFromArray( $decodedValue );
			}
		}
		return $value;
	}

	protected function makeResultRelevanceFromArray( $value ) {
		if( $this->getUser()->isLoggedIn() == false ) {
			return false;
		}
		if( isset( $value['resultId'] ) && isset( $value['value'] ) ) {
			return new \BS\ExtendedSearch\ResultRelevance(
				$this->getUser(),
				$value['resultId'],
				$value['value']
			);
		}

		return false;
	}
	/**
	 *
	 * @var \BS\ExtendedSearch\ResultRelevance
	 */
	protected $resultRelevance = null;

	protected function readInParameters() {
		$this->resultRelevance = $this->getParameter( 'relevanceData' );
	}

	/**
	 *
	 * @var boolean $status
	 */
	protected $status;
	protected function applyRelevanceChange() {
		$status = $this->resultRelevance->save();
		$this->status = $status ? 1 : 0;
	}

	protected function returnResults() {
		$result = $this->getResult();
		$result->addValue( null , 'status', $this->status );
	}
}
