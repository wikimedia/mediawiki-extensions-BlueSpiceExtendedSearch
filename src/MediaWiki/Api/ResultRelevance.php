<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Json\FormatJson;
use Wikimedia\ParamValidator\ParamValidator;

class ResultRelevance extends ApiBase {
	public function execute() {
		$this->readInParameters();
		$this->applyRelevanceChange();
		$this->returnResults();
	}

	/**
	 *
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'relevanceData' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-relevance-data',
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
		if ( $paramName === 'relevanceData' ) {
			$decodedValue = FormatJson::decode( $value, true );
			if ( is_array( $decodedValue ) ) {
				return $this->makeResultRelevanceFromArray( $decodedValue );
			}
		}
		return $value;
	}

	/**
	 *
	 * @param array $value
	 * @return \BS\ExtendedSearch\ResultRelevance|false
	 */
	protected function makeResultRelevanceFromArray( $value ) {
		if ( $this->getUser()->isRegistered() == false ) {
			return false;
		}
		if ( isset( $value['resultId'] ) && isset( $value['value'] ) ) {
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
	 * @var bool
	 */
	protected $status;

	protected function applyRelevanceChange() {
		$status = $this->resultRelevance->save();
		$this->status = $status ? 1 : 0;
	}

	protected function returnResults() {
		$result = $this->getResult();
		$result->addValue( null, 'status', $this->status );
	}
}
