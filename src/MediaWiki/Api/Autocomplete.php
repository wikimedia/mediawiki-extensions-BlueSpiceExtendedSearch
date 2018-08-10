<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class Autocomplete extends \ApiBase {
	public function execute() {
		$this->readInParameters();
		$this->lookUpResults();
		$this->setPageCreatable();
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
			],
			'searchData' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-search_data',
			]
		];
	}

	protected function getParameterFromSettings( $paramName, $paramSettings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $paramName, $paramSettings, $parseLimit );
		if ( $paramName === 'q' ) {
			$decodedValue = \FormatJson::decode( $value, true );
			if( is_array( $decodedValue ) ) {
				return new \BS\ExtendedSearch\Lookup( $decodedValue );
			}
		}
		if( $paramName === 'searchData' ) {
			return \FormatJson::decode( $value, true );
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
		$this->searchData = $this->getParameter( 'searchData' );
	}

	protected $pageCreateInfo;
	protected function setPageCreatable() {
		$pageName = $this->searchData['value'];
		$pageName = ucfirst( $pageName );

		$title = \Title::makeTitle(
			$this->searchData['namespace'],
			$pageName
		);

		if( $title->exists() == false && $title->userCan( 'createpage' ) && $title->userCan( 'edit' ) ) {
			$this->pageCreatable = true;
			$this->pageCreateInfo = [
				'display_text' => $title->getFullText(),
				'full_url' => $title->getFullURL( ['action' => 'edit'] ),
				'creatable' => 1
			];
		} else {
			$this->pageCreateInfo = [
				'creatable' => 0
			];
		}
	}

	/**
	 *
	 * @var array $suggestions
	 */
	protected $suggestions;
	protected function lookUpResults() {
		$oBackend = \BS\ExtendedSearch\Backend::instance( $this->sBackend );
		$this->suggestions = $oBackend->runAutocompleteLookup( $this->oLookup, $this->searchData );
	}

	protected $oResult;
	protected function returnResults() {
		$oResult = $this->getResult();

		$oResult->addValue( null , 'suggestions', $this->suggestions );
		$oResult->addValue( null, 'page_create_info', $this->pageCreateInfo );
	}
}