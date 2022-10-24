<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

use Wikimedia\ParamValidator\ParamValidator;

class Stats extends \ApiBase {

	/**
	 *
	 * @var \BS\ExtendedSearch\Backend
	 */
	protected $backend = [];

	public function execute() {
		$result = $this->getResult();
		$stats = [];

		$this->backend = \BS\ExtendedSearch\Backend::instance();

		try {
			$stats = $this->makeBackendStats( $this->backend );
		}
		catch ( \Exception $ex ) {
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
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-stats-param-stats',
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
			$value = \FormatJson::decode( $value, true );
			if ( empty( $value ) ) {
				return [];
			}
		}
		return $value;
	}

	/**
	 *
	 * @param \BS\ExtendedSearch\Backend $bac
	 * @return array The stats
	 */
	protected function makeBackendStats( $bac ) {
		$stats = [
			'all_documents_count' => $this->backend->getIndexByType( '*' )->count(),
			'sources' => []
		];
		$sources = $this->backend->getSources();

		foreach ( $sources as $source ) {
			$typeKey = $source->getTypeKey();
			$stats['sources'][$typeKey] = [
				// give grep a chance to find:
				// bs-extendedsearch-source-label-wikipage
				// bs-extendedsearch-source-label-specialpage
				// bs-extendedsearch-source-label-external
				// bs-extendedsearch-source-label-repofile
				'label' => wfMessage( 'bs-extendedsearch-source-label-' . $typeKey )->plain(),
				'pending_update_jobs' => $source->getCrawler()->getNumberOfPendingJobs(),
				'documents_count' => $this->backend->getIndexByType( $typeKey )->count()
			];
		}

		return $stats;
	}
}
