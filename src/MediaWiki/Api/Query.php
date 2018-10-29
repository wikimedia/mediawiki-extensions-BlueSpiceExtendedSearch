<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class Query extends \ApiBase {
	protected $searchTerm;
	protected $pageCreateData = [];

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
			'searchTerm' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-search-term'
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
				$oLookup->setQueryString( $value );
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
		$this->searchTerm = $this->getParameter( 'searchTerm' );
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

		if( isset( $this->resultSet->exception ) ) {
			//Search query caused an exception - usually malformed query
			$oResult->addValue( null, 'exception', 1 );
			$oResult->addValue( null, 'exceptionType', $this->resultSet->exceptionType );
			return;
		}

		$oResult->addValue( null , 'results', $this->resultSet->results );
		$oResult->addValue( null , 'total', $this->resultSet->total );
		$oResult->addValue( null , 'filters', $this->resultSet->filters );
		$oResult->addValue( null , 'spellcheck', $this->resultSet->spellcheck );
		$oResult->addValue( null , 'lookup', \FormatJson::encode( $this->oLookup ) );
		$oResult->addValue( null , 'total_approximated', $this->resultSet->total_approximated );
		if( !empty( $this->pageCreateData ) ) {
			$oResult->addValue( null , 'page_create_data', $this->pageCreateData );
		}
	}

	protected function setPageCreatable() {
		if( !$this->searchTerm ) {
			return;
		}
		$pageName = $this->searchTerm;

		if( $this->getConfig()->get( 'CapitalLinks' ) ) {
			$pageName = ucfirst( $pageName );
		}

		$title = \Title::newFromText( $pageName );

		if( $title instanceof \Title === false ) {
			return;
		}

		if( $title->exists() == false && $title->userCan( 'createpage' ) && $title->userCan( 'edit' ) ) {
			$this->pageCreateData = [
				'title' => $title->getPrefixedText(),
				'url' => $title->getLocalURL( [ 'action' => 'edit' ] )
			];
		}
	}
}
