<?php

namespace BS\ExtendedSearch\Source;

class ExternalFiles extends DecoratorBase {
	/**
	 * @param Base $base
	 * @return ExternalFiles
	 */
	public static function create( $base ) {
		return new self( $base );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\ExternalFile
	 */
	public function getCrawler() {
		return new Crawler\ExternalFile( $this->getConfig() );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\File
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\File(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\MappingProvider\File
	 */
	public function getMappingProvider() {
		return new MappingProvider\File(
			$this->oDecoratedSource->getMappingProvider()
		);
	}

	public function getFormatter() {
		return new Formatter\ExternalFileFormatter( $this );
	}

	/**
	 *
	 * @param array $aDocumentConfigs
	 * @return \Elastica\Bulk\ResponseSet
	 */
	public function addDocumentsToIndex( $aDocumentConfigs ) {
		$oElasticaIndex = $this->getBackend()->getIndexByType( $this->getTypeKey() );
		$oType = $oElasticaIndex->getType( $this->getTypeKey() );
		$aDocs = [];
		foreach( $aDocumentConfigs as $aDC ) {
			$document = new \Elastica\Document( $aDC['id'], $aDC );
			$aDocs[] = $document;
		}

		$bulk = new \Elastica\Bulk( $oElasticaIndex->getClient() );
		$bulk->setType( $oType );
		$bulk->setRequestParam( 'pipeline', 'file_data' );
		$bulk->addDocuments( $aDocs );
		$oResult = $bulk->send();

		if( !$oResult->isOk() ) {
			wfDebugLog(
				'BSExtendedSearch',
				"Adding documents failed: {$oResult->getError()}"
			);
		}
		$oElasticaIndex->refresh();

		return $oResult;
	}

	public function getSearchPermission() {
		return 'extendedsearch-search-externalfile';
	}
}