<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\MappingProvider\File as FileMappingProvider;
use Elastica\Document;
use Elastica\Bulk\ResponseSet;
use Elastica\Bulk;
use Elastica\Exception\Bulk\ResponseException;
use Elastica\Index;
use Elastica\Request as ElasticaRequest;
use Elastica\Client as ElasticaClient;

class Files extends DecoratorBase {

	/**
	 * @param Base $base
	 * @return Files
	 */
	public static function create( $base ) {
		return new static( $base );
	}

	/**
	 *
	 * @return FileMappingProvider
	 */
	public function getMappingProvider() {
		return new FileMappingProvider(
			$this->oDecoratedSource->getMappingProvider()
		);
	}

	/**
	 *
	 * @param array $documentConfigs
	 * @return ResponseSet
	 */
	public function addDocumentsToIndex( $documentConfigs ) {
		/** @var $elasticaIndex $elasticaIndex */
		$elasticaIndex = $this->getBackend()->getIndexByType( $this->getTypeKey() );
		$docs = [];
		foreach ( $documentConfigs as $dc ) {
			$document = new Document( $dc['id'], $dc );
			$docs[] = $document;
		}

		try {
			// Try normal index, with content
			$result = $this->runBulk( $elasticaIndex, $docs, true );
		} catch ( ResponseException $exception ) {
			if ( !$this->getConfig()->get( 'ESAllowIndexingDocumentsWithoutContent' ) ) {
				throw $exception;
			}
			// If it failed, try indexing without content
			$result = $this->runBulk( $elasticaIndex, $docs );
		}

		if ( !$result->isOk() ) {
			wfDebugLog(
				'BSExtendedSearch',
				"Adding documents failed: {$result->getError()}"
			);
		}
		$elasticaIndex->refresh();

		return $result;
	}

	/**
	 * @param Index $index
	 * @param array $docs
	 * @param bool $includeContent
	 * @return ResponseSet
	 */
	protected function runBulk( $index, $docs, $includeContent = false ) {
		$bulk = new Bulk( $index->getClient() );
		$bulk->setType( $index->getType( $this->getTypeKey() ) );
		if ( $includeContent ) {
			$bulk->setRequestParam( 'pipeline', 'file_data' );
		}
		$bulk->addDocuments( $docs );
		return $bulk->send();
	}

	/**
	 * @param ElasticaClient $client
	 */
	public function runAdditionalSetupRequests( ElasticaClient $client ) {
		$client->request(
			"_ingest/pipeline/file_data",
			ElasticaRequest::PUT,
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
}
