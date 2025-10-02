<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ISearchCrawler;
use BS\ExtendedSearch\ISearchDocumentProvider;
use BS\ExtendedSearch\ISearchMappingProvider;
use BS\ExtendedSearch\ISearchResultFormatter;
use BS\ExtendedSearch\ISearchSource;
use BS\ExtendedSearch\ISearchUpdater;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\PostProcessor;
use BS\ExtendedSearch\Source\LookupModifier\BaseAutocompleteSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\BaseConvertTypeFilter;
use BS\ExtendedSearch\Source\LookupModifier\BaseExtensionAggregation;
use BS\ExtendedSearch\Source\LookupModifier\BaseMTimeBoost;
use BS\ExtendedSearch\Source\LookupModifier\BaseSimpleQSFields;
use BS\ExtendedSearch\Source\LookupModifier\BaseSortByID;
use BS\ExtendedSearch\Source\LookupModifier\BaseTagsAggregation;
use BS\ExtendedSearch\Source\LookupModifier\BaseTitleSecurityTrimmings;
use BS\ExtendedSearch\Source\LookupModifier\BaseTypeSecurityTrimming;
use BS\ExtendedSearch\Source\LookupModifier\BaseUserRelevance;
use BS\ExtendedSearch\Source\LookupModifier\BaseWildcarder;
use BS\ExtendedSearch\Source\LookupModifier\RegExpQuoter;
use BS\ExtendedSearch\Source\LookupModifier\SearchContext;
use BS\ExtendedSearch\Source\PostProcessor\Base as PostProcessorBase;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use OpenSearch\Client;
use Wikimedia\ObjectFactory\ObjectFactory;

class GenericSource implements ISearchSource {

	/** @var Backend */
	protected $backend;

	/** @var Config */
	protected $config;
	/** @var ObjectFactory */
	protected $objectFactory;

	/**
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct( ObjectFactory $objectFactory ) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @param Backend $backend
	 *
	 * @return void
	 */
	public function setBackend( Backend $backend ) {
		$this->backend = $backend;
	}

	/**
	 * @param Config $config
	 *
	 * @return void
	 */
	public function setSourceConfig( Config $config ) {
		$this->config = $config;
	}

	/**
	 *
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @inheritDoc
	 */
	public function getBackend(): Backend {
		return $this->backend;
	}

	/**
	 * @inheritDoc
	 */
	public function getTypeKey(): string {
		return $this->getConfig()->get( 'sourcekey' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMappingProvider(): ISearchMappingProvider {
		return new MappingProvider\Base();
	}

	/**
	 * @inheritDoc
	 */
	public function getCrawler(): ISearchCrawler {
		return $this->makeObjectFromSpec( [
			'class' => Crawler\Base::class,
			'args' => [ $this->getConfig() ],
			'services' => [ 'DBLoadBalancer', 'JobQueueGroup' ]
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getDocumentProvider(): ISearchDocumentProvider {
		return new DocumentProvider\Base();
	}

	/**
	 * @inheritDoc
	 */
	public function getUpdater(): ISearchUpdater {
		return new Updater\Base( $this );
	}

	/**
	 * @param array $spec
	 *
	 * @return mixed
	 */
	protected function makeObjectFromSpec( array $spec ) {
		return $this->objectFactory->createObject( $spec );
	}

	/**
	 *
	 * @return array
	 */
	public function getIndexSettings(): array {
		// This kind of tokenizing breaks words in 3-char parts,
		// which makes it possible to match single words in compound words
		return [
			"settings" => [
				// Only for testing purposes on small sample, remove or increase for production
				// "number_of_shards" => 1,
				"index" => [
					"max_ngram_diff" => 20
				],
				"analysis" => [
					"normalizer" => [
						"lowercase" => [
							"type" => "custom",
							"char_filter" => [],
							"filter" => [ "lowercase", "asciifolding" ]
						]
					],
					"analyzer" => [
						"substring_analyzer" => [
							"tokenizer" => "substring",
							"filter" => [ "lowercase", "asciifolding" ]
						],
						"content_analyzer" => [
							"tokenizer" => "whitespace",
							"filter" => [ "lowercase", "asciifolding" ]
						],
					],
					"tokenizer" => [
						"substring" => [
							"type" => "ngram",
							"min_gram" => 3,
							"max_gram" => 20,
							"token_chars" => [ "letter", "digit" ]
						]
					],
				],
			]
		];
	}

	/**
	 *
	 * @param Client $client
	 *
	 * @return bool
	 */
	public function runAdditionalSetupRequests( Client $client ): bool {
		return true;
	}

	/**
	 *
	 * @param array $document
	 * @return bool
	 */
	public function addDocumentToIndex( $document ): bool {
		if ( $this->backend->isReadOnly() ) {
			return false;
		}
		$params = [
			'index' => $this->getBackend()->getIndexName( $this->getTypeKey() ),
			'id' => $document['id'],
			'refresh' => true,
			'body' => $document,
		];

		$this->modifyRequestParams( 'add', $params );
		$res = $this->getBackend()->getClient()->index( $params );

		return is_array( $res ) &&
			isset( $res['result'] ) &&
			( $res['result'] === 'created' || $res['result'] === 'updated' );
	}

	/**
	 *
	 * @param string $documentId
	 *
	 * @return bool
	 */
	public function deleteDocumentFromIndex( string $documentId ): bool {
		if ( $this->backend->isReadOnly() ) {
			return false;
		}
		$indexName = $this->getBackend()->getIndexName( $this->getTypeKey() );
		$res = $this->getBackend()->getClient()->delete( [
			'index' => $indexName,
			'id' => $documentId,
			'refresh' => true,
		] );
		return is_array( $res ) && isset( $res['result'] ) && $res['result'] === 'deleted';
	}

	/**
	 * @param string $action
	 * @param array &$params
	 *
	 * @return void
	 */
	protected function modifyRequestParams( string $action, array &$params ) {
		// NOOP
	}

	/**
	 *
	 * @return Formatter\Base
	 */
	public function getFormatter(): ISearchResultFormatter {
		return new Formatter\Base( $this );
	}

	/**
	 *
	 * @return string
	 */
	public function getSearchPermission(): string {
		// Default - no permission required
		return '';
	}

	/**
	 * Can fields in this source be used for sorting
	 *
	 * @return bool
	 */
	public function isSortable() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getLookupModifiers( Lookup $lookup, IContextSource $context ): array {
		return [
			new BaseExtensionAggregation( $lookup, $context ),
			new BaseTagsAggregation( $lookup, $context ),
			new BaseSimpleQSFields( $lookup, $context ),
			BaseWildcarder::factory( MediaWikiServices::getInstance(), $lookup, $context ),
			new BaseSortByID( $lookup, $context ),
			new BaseUserRelevance( $lookup, $context ),
			new BaseTypeSecurityTrimming( $lookup, $context ),
			new BaseTitleSecurityTrimmings( $this->getBackend(), $lookup, $context ),
			new BaseMTimeBoost( $lookup, $context ),
			new BaseAutocompleteSourceFields( $lookup, $context ),
			new BaseConvertTypeFilter( $lookup, $context ),
			new RegExpQuoter( $lookup, $context ),
			new SearchContext(
				$lookup, $context,
				MediaWikiServices::getInstance()->getService( 'BSExtendedSearch.PluginManager' )
			)
		];
	}

	/**
	 * @param PostProcessor $postProcessorRunner
	 *
	 * @return PostProcessorBase[]
	 */
	public function getPostProcessors( PostProcessor $postProcessorRunner ): array {
		return [ new PostProcessorBase( $postProcessorRunner ) ];
	}
}
