<?php

namespace BS\ExtendedSearch\Source;

class Base {

	/**
	 *
	 * @var \BS\ExtendedSearch\Backend
	 */
	protected $oBackend = null;

	/**
	 *
	 * @var \Config
	 */
	protected $oConfig = null;

	/**
	 *
	 * @param \BS\ExtendedSearch\Backend
	 * @param array $aConfig
	 */
	public function __construct( $oBackend, $aConfig ) {
		$this->oBackend = $oBackend;
		$this->oConfig = new \HashConfig( $aConfig );
	}

	/**
	 *
	 * @return \Config
	 */
	public function getConfig() {
		return $this->oConfig;
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Backend
	 */
	public function getBackend() {
		return $this->oBackend;
	}

	/**
	 *
	 * @return string
	 */
	public function getTypeKey() {
		return $this->getConfig()->get( 'sourcekey' );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\MappingProvider\Base
	 */
	public function getMappingProvider() {
		return new MappingProvider\Base();
	}

	/**
	 * @return BS\ExtendedSearch\Crawler\Base
	 */
	public function getCrawler() {
		return new Crawler\Base( $this->oConfig );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\Base
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\Base();
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Updater\Base
	 */
	public function getUpdater() {
		return new Updater\Base( $this );
	}

	/**
	 *
	 * @param \IContextSource $oContext
	 * @return BS\ExtendedSearch\Source\QueryProcessor\Base[]
	 */
	public function getQueryProcessors( $oContext ) {
		return [];
	}

	/**
	 *
	 * @return array
	 */
	public function getIndexSettings() {
		return [];
	}

	/**
	 *
	 * @param array $aDocumentConfigs
	 * @return \Elastica\Bulk\ResponseSet
	 */
	public function addDocumentsToIndex( $aDocumentConfigs ) {
		$oElasticaIndex = $this->getBackend()->getIndex();
		$oType = $oElasticaIndex->getType( $this->getTypeKey() );
		$aDocs = [];
		foreach( $aDocumentConfigs as $aDC ) {
			$aDocs[] = new \Elastica\Document( $aDC['id'], $aDC );
		}

		$oResult = $oType->addDocuments( $aDocs );
		if( !$oResult->isOk() ) {
			wfDebugLog(
				'BSExtendedSearch',
				"Adding documents failed: {$oResult->getError()}"
			);
		}
		$oElasticaIndex->refresh();

		return $oResult;
	}

	/**
	 *
	 * @param array $aDocumentIds
	 * @return \Elastica\Bulk\ResponseSet
	 */
	public function deleteDocumentsFromIndex( $aDocumentIds ) {
		$oElasticaIndex = $this->getBackend()->getIndex();
		$aDocs = [];
		foreach ( $aDocumentIds as $sDocumentId ) {
			$aDocs[] = new \Elastica\Document( $sDocumentId );
		}

		$oResult = $oElasticaIndex->deleteDocuments( $aDocs );
		if( !$oResult->isOk() ) {
			wfDebugLog(
				'BSExtendedSearch',
				"Adding documents failed: {$oResult->getError()}"
			);
		}

		$oElasticaIndex->refresh();

		return $oResult;
	}

	#abstract public function getFormatter();
}