<?php

namespace BS\ExtendedSearch\MediaWiki\Api;

class Stats extends \ApiBase {

	/**
	 *
	 * @var \BS\ExtendedSearch\Backend
	 */
	protected $aBackends = [];

	public function execute() {
		$oResult = $this->getResult();
		$aStats = [];

		$this->aBackends = \BS\ExtendedSearch\Backend::factoryAll();
		
		foreach( $this->aBackends as $sBackendKey => $oBackend ) {
			try {
				$aStats[ $sBackendKey ] = $this->makeBackendStats( $oBackend );
			}
			catch ( \Exception $ex ) {
				$aStats[ $sBackendKey ] = [
					'error' => $ex->getMessage()
				];
			}
		}		

		$oResult->addValue( null , 'stats', $aStats );
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
	 * @param \BS\ExtendedSearch\Backend $oBackend
	 * @return array The stats
	 */
	protected function makeBackendStats( $oBackend ) {
		$aStats = [
			'all_documents_count' => $oBackend->getIndex()->count(),
			'sources' => []
		];
		$aSources = $oBackend->getSources();

		foreach( $aSources as $oSource ) {
			$sTypeKey = $oSource->getTypeKey();
			$aStats['sources'][$sTypeKey] = [
				//give grep a chance to find:
				//bs-extendedsearch-source-label-wikipage
				//bs-extendedsearch-source-label-specialpage
				//bs-extendedsearch-source-label-external
				//bs-extendedsearch-source-label-repofile
				'label' => wfMessage( 'bs-extendedsearch-source-label-' . $sTypeKey )->plain(),
				'pending_update_jobs' => $oSource->getCrawler()->getNumberOfPendingJobs(),
				'documents_count' => $oBackend->getIndex()->getType( $sTypeKey )->count()
			];
		}

		return $aStats;
	}
}