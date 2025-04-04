<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use BS\ExtendedSearch\Backend;
use MediaWiki\Api\ApiBase;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class Stats extends ApiBase {

	/**
	 *
	 * @var Backend
	 */
	protected $backend = [];

	public function execute() {
		$result = $this->getResult();
		$this->backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );

		try {
			$stats = $this->makeBackendStats( $this->backend );
		} catch ( \Exception $ex ) {
			$stats = [
				'error' => $ex->getMessage()
			];
		}

		$result->addValue( null, 'stats', $stats );
	}

	/**
	 *
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'stats' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => '[]',
				ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-stats-param-stats',
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
		if ( $paramName === 'stats' ) {
			$value = FormatJson::decode( $value, true );
			if ( empty( $value ) ) {
				return [];
			}
		}
		return $value;
	}

	/**
	 *
	 * @param Backend $backend
	 *
	 * @return array The stats
	 * @throws \Exception
	 */
	protected function makeBackendStats( Backend $backend ): array {
		$countStats = $this->getCountStats( $backend->getIndexName( '*' ), $backend );
		$stats = [
			'all_documents_count' => $countStats['count'] ?? -1,
			'shards' => $countStats['_shards'] ?? [],
			'sources' => []
		];
		$sources = $backend->getSources();

		foreach ( $sources as $source ) {
			$typeKey = $source->getTypeKey();
			$stats['sources'][$typeKey] = [
				// give grep a chance to find:
				// bs-extendedsearch-source-label-wikipage
				// bs-extendedsearch-source-label-specialpage
				// bs-extendedsearch-source-label-external
				// bs-extendedsearch-source-label-repofile
				'label' => $this->msg( 'bs-extendedsearch-source-label-' . $typeKey )->plain(),
				'pending_update_jobs' => $source->getCrawler()->getNumberOfPendingJobs(),
				'documents_count' => $this->getCountStats( $backend->getIndexName( $typeKey ), $backend )['count'] ?? -1
			];
		}
		$stats['backend_info'] = $backend->getClient()->info();

		return $stats;
	}

	/**
	 * @param string $index
	 * @param Backend $backend
	 *
	 * @return array
	 */
	protected function getCountStats( $index, Backend $backend ): array {
		return $backend->getClient()->count( [ 'index' => $index ] );
	}
}
