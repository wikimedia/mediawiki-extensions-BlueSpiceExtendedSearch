<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

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

		$result->addValue( null , 'stats', $stats );
	}

	protected function getAllowedParams() {
		return [
			'stats' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false,
				\ApiBase::PARAM_DFLT => '[]',
				\ApiBase::PARAM_HELP_MSG => 'apihelp-bs-extendedsearch-stats-param-stats',
			]
		];
	}

	protected function getParameterFromSettings( $paramName, $paramSettings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $paramName, $paramSettings, $parseLimit );
		if ( $paramName === 'stats' ) {
			$value = \FormatJson::decode( $value, true );
			if( empty( $value ) ) {
				return [];
			}
		}
		return $value;
	}

	/**
	 *
	 * @return array The stats
	 */
	protected function makeBackendStats( $bac ) {
		$stats = [
			'all_documents_count' => $this->backend->getIndexByType( '*' )->count(),
			'sources' => []
		];
		$sources = $this->backend->getSources();

		foreach( $sources as $source ) {
			$typeKey = $source->getTypeKey();
			$stats['sources'][$typeKey] = [
				//give grep a chance to find:
				//bs-extendedsearch-source-label-wikipage
				//bs-extendedsearch-source-label-specialpage
				//bs-extendedsearch-source-label-external
				//bs-extendedsearch-source-label-repofile
				'label' => wfMessage( 'bs-extendedsearch-source-label-' . $typeKey )->plain(),
				'pending_update_jobs' => $source->getCrawler()->getNumberOfPendingJobs(),
				'documents_count' => $this->backend->getIndexByType( $typeKey )->count()
			];
		}

		return $stats;
	}
}
