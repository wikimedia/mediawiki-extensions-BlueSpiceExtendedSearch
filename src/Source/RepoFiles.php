<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\LookupModifier\FileContent;
use BS\ExtendedSearch\Source\LookupModifier\Base as LookupModifier;

class RepoFiles extends DecoratorBase {
	/**
	 * @param Base $base
	 * @return RepoFiles
	 */
	public static function create( $base ) {
		return new self( $base );
	}

	protected $lookupModifiers = [
		LookupModifier::TYPE_SEARCH => [
			'file-content' => FileContent::class
		],
		LookupModifier::TYPE_AUTOCOMPLETE => [
		]
	];

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\RepoFile
	 */
	public function getCrawler() {
		return new Crawler\RepoFile( $this->getConfig() );
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

	public function getUpdater() {
		return new Updater\RepoFile( $this );
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
		return new Formatter\FileFormatter( $this );
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

	public function runAdditionalSetupRequests( \Elastica\Client $client ) {
		$client->request(
			"_ingest/pipeline/file_data",
			\Elastica\Request::PUT,
			[
				"description" => "Extract file information",
				"processors" => [ [
					"attachment" => [
						"field" => "the_file"
					]
				] ]
			]
		);
	}

	public function getSearchPermission() {
		return 'extendedsearch-search-repofile';
	}
}