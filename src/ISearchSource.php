<?php

namespace BS\ExtendedSearch;

use MediaWiki\Config\Config;
use OpenSearch\Client;

interface ISearchSource extends ILookupModifierProvider, IPostProcessorProvider {
	/**
	 * @param Backend $backend
	 *
	 * @return void
	 */
	public function setBackend( Backend $backend );

	/**
	 * @param Config $config
	 *
	 * @return mixed
	 */
	public function setSourceConfig( Config $config );

	/**
	 * @return Backend
	 */
	public function getBackend(): Backend;

	/**
	 *
	 * @return string
	 */
	public function getTypeKey(): string;

	/**
	 * @return ISearchMappingProvider
	 */
	public function getMappingProvider(): ISearchMappingProvider;

	/**
	 * @return ISearchCrawler
	 */
	public function getCrawler(): ISearchCrawler;

	/**
	 * @return ISearchDocumentProvider
	 */
	public function getDocumentProvider(): ISearchDocumentProvider;

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Updater\Base
	 */
	public function getUpdater(): ISearchUpdater;

	/**
	 * Settings to use when creating the index
	 * eg. [ 'settings' => [ 'number_of_shards' => 1 ] ]
	 * @return array
	 */
	public function getIndexSettings(): array;

	/**
	 *
	 * @param Client $client
	 *
	 * @return bool
	 */
	public function runAdditionalSetupRequests( Client $client ): bool;

	/**
	 *
	 * @param array $document
	 * @return bool
	 */
	public function addDocumentToIndex( $document ): bool;

	/**
	 *
	 * @param string $documentId
	 *
	 * @return bool
	 */
	public function deleteDocumentFromIndex( string $documentId ): bool;

	/**
	 * @return ISearchResultFormatter
	 */
	public function getFormatter(): ISearchResultFormatter;

	/**
	 *
	 * @return string
	 */
	public function getSearchPermission(): string;

	/**
	 * Can fields in this source be used for sorting
	 *
	 * @return bool
	 */
	public function isSortable();
}
