<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\ISearchDocumentProvider;
use BS\ExtendedSearch\ISearchMappingProvider;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Source\DocumentProvider\File;
use BS\ExtendedSearch\Source\LookupModifier\FileAutocompleteSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\FileContent;
use BS\ExtendedSearch\Source\MappingProvider\File as FileMappingProvider;
use Exception;
use MediaWiki\Context\IContextSource;
use OpenSearch\Client;

class Files extends GenericSource {
	/** @var bool */
	private $noPipeline = false;
	/** @var bool */
	private $failedOnce = false;

	/**
	 *
	 * @return FileMappingProvider
	 */
	public function getMappingProvider(): ISearchMappingProvider {
		return new FileMappingProvider();
	}

	/**
	 *
	 * @param array $document
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function addDocumentToIndex( $document ): bool {
		try {
			return parent::addDocumentToIndex( $document );
		} catch ( Exception $ex ) {
			if ( !$this->getConfig()->get( 'ESAllowIndexingDocumentsWithoutContent' ) ) {
				throw $ex;
			}
			if ( $this->failedOnce ) {
				throw $ex;
			}
			$this->failedOnce = true;
			// Temp disable settings pipeline to allow indexing without file content
			$this->noPipeline = true;
			$res = $this->addDocumentToIndex( $document );
			$this->noPipeline = false;
			return $res;
		}
	}

	/**
	 *
	 * @return ISearchDocumentProvider
	 */
	public function getDocumentProvider(): ISearchDocumentProvider {
		return $this->makeObjectFromSpec( [
			'class' => File::class,
			'services' => [ 'MimeAnalyzer' ]
		] );
	}

	/**
	 * @param string $action
	 * @param array &$params
	 *
	 * @return void
	 */
	protected function modifyRequestParams( string $action, array &$params ) {
		if ( $action !== 'add' || $this->noPipeline ) {
			return;
		}
		$params['pipeline'] = 'file_data';
	}

	/**
	 * Register ingest pipeline
	 * @param Client $client
	 *
	 * @return bool
	 */
	public function runAdditionalSetupRequests( Client $client ): bool {
		$res = $client->ingest()->putPipeline( [
			'id' => 'file_data',
			'body' => [
				'description' => 'Extract file information',
				'processors' => [ [
					'attachment' => [
						'field' => 'the_file'
					]
				] ]
			]
		] );

		return is_array( $res ) && isset( $res['acknowledged'] ) && $res['acknowledged'];
	}

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 *
	 * @return array
	 */
	public function getLookupModifiers( Lookup $lookup, IContextSource $context ): array {
		$parent = parent::getLookupModifiers( $lookup, $context );
		$parent[] = new FileContent( $lookup, $context );
		$parent[] = new FileAutocompleteSourceFields( $lookup, $context );
		return $parent;
	}
}
