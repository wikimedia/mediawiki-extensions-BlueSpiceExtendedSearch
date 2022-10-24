<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class Autocomplete extends \ApiBase {
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
		$this->setPageCreatable();
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
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-q',
			],
			'backend' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 'local',
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-backend',
			],
			'searchData' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-search-data',
			],
			'secondaryRequestData' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-query-param-secondary-request-data',
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
			$decodedValue = \FormatJson::decode( $value, true );
			if ( is_array( $decodedValue ) ) {
				return new \BS\ExtendedSearch\Lookup( $decodedValue );
			}
		}
		if ( $paramName === 'searchData' ) {
			return \FormatJson::decode( $value, true );
		}

		if ( $paramName === 'secondaryRequestData' ) {
			return \FormatJson::decode( $value, true );
		}

		return $value;
	}

	protected function readInParameters() {
		$this->lookup = $this->getParameter( 'q' );
		$this->backend = $this->getParameter( 'backend' );
		$this->searchData = $this->getParameter( 'searchData' );
		$this->secondaryRequestData = $this->getParameter( 'secondaryRequestData' );
	}

	/** @var array|null */
	protected $pageCreateInfo;

	protected function setPageCreatable() {
		$pageName = $this->searchData['value'];
		if ( isset( $this->searchData[ 'mainpage' ] ) && $this->searchData[ 'mainpage' ] !== '' ) {
			$pageName = $this->searchData[ 'mainpage' ] . '/' . $pageName;
		}

		if ( $this->getConfig()->get( 'CapitalLinks' ) ) {
			$pageName = ucfirst( $pageName );
		}

		$title = \Title::makeTitle(
			$this->searchData['namespace'],
			$pageName
		);
		$user = $this->getUser();
		$pm = MediaWikiServices::getInstance()->getPermissionManager();

		if ( $title->exists() == false &&
			$pm->userCan( 'createpage', $user, $title ) &&
			$pm->userCan( 'edit', $user, $title )
		) {
			$this->pageCreatable = true;

			$linkRenderer = MediaWikiServices::getInstance()->getService( 'LinkRenderer' );
			$anchorText = wfMessage(
				'bs-extendedsearch-autocomplete-create-page-link',
				$title->getFullText()
			)->plain();
			$anchor = $linkRenderer->makeLink( $title, $anchorText, [], [ 'action' => 'edit' ] );

			$this->pageCreateInfo = [
				'creatable' => 1,
				'anchor' => $anchor
			];
		} else {
			$this->pageCreateInfo = [
				'creatable' => 0
			];
		}
	}

	/**
	 *
	 * @var array
	 */
	protected $suggestions;

	protected function lookUpResults() {
		$backend = \BS\ExtendedSearch\Backend::instance( $this->backend );
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

	/** @var \ApiResult */
	protected $oResult;

	protected function returnResults() {
		$oResult = $this->getResult();

		$oResult->addValue( null, 'suggestions', $this->suggestions );
		$oResult->addValue( null, 'page_create_info', $this->pageCreateInfo );
	}
}
