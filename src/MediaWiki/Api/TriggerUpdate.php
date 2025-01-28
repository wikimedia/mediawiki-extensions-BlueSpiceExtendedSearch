<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;

class TriggerUpdate extends ApiBase {
	public function execute() {
		$sBackendKey = $this->getParameter( 'backend' );
		$sSourceKey = $this->getParameter( 'source' );
		$oTitle = Title::newFromText( $this->getParameter( 'title' ) );
		$aParams = $this->getParameter( 'params' );

		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSExtendedSearchTriggerUpdate',
			[
				$sBackendKey,
				$sSourceKey,
				$oTitle,
				$aParams
			]
		);
	}

	/**
	 *
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'backend' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 'local',
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-backend',
			],
			'source' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-generic-param-sources',
			],
			'title' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-triggerupdate-param-title',
			],
			'params' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => '[]',
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-triggerupdate-param-params',
			]
		];
	}
}
