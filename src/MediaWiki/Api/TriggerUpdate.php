<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class TriggerUpdate extends \ApiBase {
	public function execute() {

		$sBackendKey = $this->getParameter( 'backend' );
		$sSourceKey = $this->getParameter( 'source' );
		$oTitle = \Title::newFromText( $this->getParameter( 'title' ) );
		$aParams = $this->getParameter( 'params' );

		\Hooks::run( 'BSExtendedSearchTriggerUpdate', [ $sBackendKey, $sSourceKey, $oTitle, $aParams ] );
	}

	protected function getAllowedParams() {
		return [
			'backend' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false,
				\ApiBase::PARAM_DFLT => 'local',
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-backend',
			],
			'source' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-sources',
			],
			'title' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true,
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-triggerupdate-param-title',
			],
			'params' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false,
				\ApiBase::PARAM_DFLT => '[]',
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-triggerupdate-param-params',
			]
		];
	}
}