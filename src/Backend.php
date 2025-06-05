<?php

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\Plugin\IFilterModifier;
use BS\ExtendedSearch\Plugin\IFormattingModifier;
use BS\ExtendedSearch\Plugin\IIndexProvider;
use BS\ExtendedSearch\Plugin\ILookupModifier;
use BS\ExtendedSearch\Plugin\IMappingModifier;
use BS\ExtendedSearch\Plugin\ISearchPlugin;
use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\MultiConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\WikiMap\WikiMap;
use MWException;
use MWStake\MediaWiki\Component\ManifestRegistry\ManifestRegistryFactory;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use RuntimeException;
use stdClass;
use Throwable;
use Wikimedia\Rdbms\ILoadBalancer;

class Backend {
	public const SPELLCHECK_ACTION_IGNORE = 'ignore';
	public const SPELLCHECK_ACTION_SUGGEST = 'suggest';
	public const SPELLCHECK_ACTION_REPLACED = 'replaced';

	public const QUERY_TYPE_SEARCH = 'search';
	public const QUERY_TYPE_AUTOCOMPLETE = 'autocomplete';

	/** @var MultiConfig */
	protected $config;

	/** @var ILoadBalancer */
	protected $lb;

	/** @var HookContainer */
	protected $hookContainer;

	/** @var SourceFactory */
	protected $sourceFactory;

	/** @var PluginManager */
	protected $pluginManager;

	/** @var array */
	protected $sources = [];

	/** @var Client */
	protected $client;

	/**
	 * @param Config $config
	 * @param ILoadBalancer $lb
	 * @param HookContainer $hookContainer
	 * @param SourceFactory $sourceFactory
	 * @param PluginManager $pluginManager
	 */
	public function __construct(
		Config $config, ILoadBalancer $lb, HookContainer $hookContainer,
		SourceFactory $sourceFactory, PluginManager $pluginManager
	) {
		$indexPrefix = $config->get( 'ESIndexPrefix' );
		if ( empty( $indexPrefix ) ) {
			$indexPrefix = strtolower( WikiMap::getCurrentWikiId() );
		}
		$specialConfig = [ 'index' => $indexPrefix ];
		$this->config = new MultiConfig( [ $config, new HashConfig( $specialConfig ) ] );
		$this->lb = $lb;
		$this->hookContainer = $hookContainer;
		$this->sourceFactory = $sourceFactory;
		$this->pluginManager = $pluginManager;
	}

	/**
	 *
	 * @param string $sourceKey
	 * @return ISearchSource
	 * @throws Exception
	 */
	public function getSource( $sourceKey ) {
		if ( isset( $this->sources[$sourceKey] ) ) {
			return $this->sources[$sourceKey];
		}
		$source = $this->sourceFactory->makeSource( $sourceKey, $this );
		$this->sources[$sourceKey] = $source;

		return $this->sources[$sourceKey];
	}

	/**
	 *
	 * @return ISearchSource[]
	 * @throws Exception
	 */
	public function getSources() {
		foreach ( $this->sourceFactory->getSourceKeys() as $sourceKey ) {
			$this->getSource( $sourceKey );
		}
		return $this->sources;
	}

	/**
	 * Check if indices are read-only
	 *
	 * @return bool
	 */
	public function isReadOnly() {
		foreach ( $this->getSources() as $key => $source ) {
			$settings = $this->getClient()->indices()->getSettings( [
				'index' => $this->getIndexName( $key )
			] );
			return isset( $settings['blocks']['read_only'] );
		}

		return false;
	}

	/**
	 * @return Client
	 */
	public function getClient() {
		if ( $this->client === null ) {
			$backendHost = $this->getConfig()->get( 'ESBackendHost' );
			$backendPort = $this->getConfig()->get( 'ESBackendPort' );
			$backendUsername = $this->getConfig()->get( 'ESBackendUsername' );
			$backendPassword = $this->getConfig()->get( 'ESBackendPassword' );
			$backendTransport = $this->getConfig()->get( 'ESBackendTransport' );

			$clientBuilder = new ClientBuilder();
			$clientBuilder->setHosts( [ "$backendTransport://$backendHost:$backendPort" ] );
			$clientBuilder->setRetries( 2 );
			if ( $backendUsername && $backendPassword ) {
				$clientBuilder->setBasicAuthentication( $backendUsername, $backendPassword );
			}

			$this->client = $clientBuilder->build();
		}

		return $this->client;
	}

	/**
	 * @return void
	 */
	public function deleteIndexes() {
		foreach ( $this->sources as $source ) {
			$sourceKey = $source->getTypeKey();
			if ( !$this->deleteIndex( $sourceKey ) ) {
				throw new RuntimeException( "Failed to delete index for source $sourceKey" );
			}
		}
	}

	/**
	 * Deletes all indexes
	 *
	 * @param string $sourceKey
	 *
	 * @return bool
	 */
	public function deleteIndex( string $sourceKey ): bool {
		$client = $this->getClient();
		$index = $this->getIndexName( $sourceKey );
		if ( $client->indices()->exists( [ 'index' => $index ] ) ) {
			$res = $client->indices()->delete( [ 'index' => $index ] );
			return is_array( $res ) && isset( $res['acknowledged'] ) && $res['acknowledged'];
		}
		return false;
	}

	/**
	 * @param string $sourceKey
	 *
	 * @return bool
	 * @throws MWException
	 */
	public function createIndex( $sourceKey ) {
		if ( !isset( $this->sources[$sourceKey] ) ) {
			throw new MWException( "Source \"$sourceKey\" does not exist!" );
		}
		$source = $this->sources[$sourceKey];

		$mappingProvider = $source->getMappingProvider();
		$indexSettings = $source->getIndexSettings();
		$mappingProperties = [
			'properties' => $mappingProvider->getPropertyConfig(),
		];
		$providerSource = $mappingProvider->getSourceConfig();
		if ( $providerSource ) {
			$mappingProperties['_source'] = $providerSource;
		}

		$plugins = $this->pluginManager->getPluginsImplementing( IMappingModifier::class );
		/** @var IMappingModifier $plugin */
		foreach ( $plugins as $plugin ) {
			$plugin->modifyMapping( $source, $indexSettings, $mappingProperties );
		}

		$res = $this->getClient()->indices()->create( [
			'index' => $this->getIndexName( $source->getTypeKey() ),
			'body' => array_merge( $indexSettings, [
				'mappings' => $mappingProperties,
			] ),
		] );
		$success = is_array( $res ) && isset( $res['acknowledged'] ) && $res['acknowledged'];

		return $success && $source->runAdditionalSetupRequests( $this->getClient() );
	}

	/**
	 *
	 * @param string $type
	 * @return string
	 */
	public function getIndexName( $type ) {
		return $this->getConfig()->get( 'index' ) . '_' . $type;
	}

	/**
	 *
	 * @return IContextSource
	 */
	public function getContext() {
		return RequestContext::getMain();
	}

	/**
	 *
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Collects all the lookup modifiers for particular search type
	 *
	 * @param Lookup $lookup
	 * @param string $type
	 * @return array|ILookupModifier[]
	 */
	public function getLookupModifiers( $lookup, $type ) {
		$lookupModifiers = [];

		foreach ( $this->sources as $source ) {
			$lookupModifiers = array_merge(
				$lookupModifiers, $source->getLookupModifiers( $lookup, $this->getContext() )
			);
		}
		$plugins = $this->pluginManager->getPluginsImplementing( ILookupModifierProvider::class );
		/** @var ILookupModifierProvider $plugin */
		foreach ( $plugins as $plugin ) {
			$lookupModifiers = array_merge(
				$lookupModifiers, $plugin->getLookupModifiers( $lookup, $this->getContext() )
			);
		}

		// Deduplicate based on get_class
		$deduplicated = [];
		foreach ( $lookupModifiers as $modifier ) {
			$deduplicated[get_class( $modifier )] = $modifier;
		}
		$lookupModifiers = array_values( $deduplicated );
		$lookupModifiers = array_filter( $lookupModifiers, static function ( $lookupModifier ) use ( $type ) {
			return in_array( $type, $lookupModifier->getSearchTypes() );
		} );

		$this->hookContainer->run(
			'BSExtendedSearchGetLookupModifiers',
			[ &$lookupModifiers, $lookup, $type ]
		);

		usort( $lookupModifiers, static function ( $a, $b ) {
			if ( $a->getPriority() === $b->getPriority() ) {
				return 0;
			}
			return ( $a->getPriority() > $b->getPriority() ) ? 1 : -1;
		} );

		return $lookupModifiers;
	}

	/**
	 * Runs quick query against backend
	 *
	 * @param Lookup $lookup
	 * @param array $searchData
	 * @return array
	 * @throws Exception
	 */
	public function runAutocompleteLookup( Lookup $lookup, $searchData ) {
		$lookupModifiers = $this->getLookupModifiers( $lookup, static::QUERY_TYPE_AUTOCOMPLETE );
		foreach ( $lookupModifiers as $lookupModifier ) {
			$lookupModifier->apply();
		}
		$results = $this->runRawQuery( $lookup );
		$resultData = $results->getResults();
		$postProcessor = $this->getPostProcessor( static::QUERY_TYPE_AUTOCOMPLETE );
		$postProcessor->process( $resultData, $lookup );

		return $this->formatQuerySuggestions( $resultData, $searchData );
	}

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 * @param array $searchData
	 * @param array $secondaryRequestData
	 *
	 * @return array
	 * @throws Exception
	 */
	public function runAutocompleteSecondaryLookup( Lookup $lookup, $searchData, $secondaryRequestData ) {
		$results = $this->runAutocompleteLookup( $lookup, $searchData );
		// TODO: Implement smart way of deciding when secondary results are relevant
		return $results;
	}

	/**
	 *
	 * @param array $resultData
	 * @param array $searchData
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function formatQuerySuggestions( $resultData, $searchData ) {
		$results = array_values( $this->getQuerySuggestionList( $resultData ) );
		return $this->formatSuggestions( $results, $searchData );
	}

	/**
	 *
	 * @param array $results
	 * @param array $searchData
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function formatSuggestions( $results, $searchData ) {
		$searchData['value'] = strtolower( $searchData['value'] );

		foreach ( $this->getSources() as $source ) {
			$source->getFormatter()->rankAutocompleteResults( $results, $searchData );
			// when results are ranked based on original data, it can be modified
			$source->getFormatter()->formatAutocompleteResults( $results, $searchData );
		}
		$plugins = $this->pluginManager->getPluginsImplementing( IFormattingModifier::class );
		/** @var IFormattingModifier $plugin */
		foreach ( $plugins as $plugin ) {
			$plugin->formatAutocompleteResults( $results, $searchData );
		}

		usort( $results, static function ( $e1, $e2 ) {
			if ( $e1['score'] == $e2['score'] ) {
				return 0;
			}
			return ( $e1['score'] < $e2['score'] ) ? 1 : -1;
		} );

		return $results;
	}

	/**
	 *
	 * @param array $results
	 * @return array
	 */
	protected function getQuerySuggestionList( $results ) {
		$res = [];
		foreach ( $results as $suggestion ) {
			$item = [
				"_id" => $suggestion->getId(),
				"_index" => $suggestion->getIndex(),
				"type" => $suggestion->getType(),
				"score" => $suggestion->getScore(),
				"rank" => false,
				"is_ranked" => false
			];

			$item = array_merge( $item, $suggestion->getData() );

			$res[$suggestion->getId()] = $item;
		}

		return $res;
	}

	/**
	 * @param Lookup $lookup
	 * @param array|null $limitToSources
	 *
	 * @return SearchResultSet
	 * @throws Exception
	 */
	public function runRawQuery( Lookup $lookup, ?array $limitToSources = null ): SearchResultSet {
		$limitToSources = $lookup['searchInTypes'] ?? $limitToSources;
		$indices = $this->getAllIndicesForQuery( $limitToSources );
		$excludeTypes = $lookup['excludeTypes'] ?? null;
		if ( !empty( $excludeTypes ) ) {
			$indices = array_diff( $indices, $this->getAllIndicesForQuery( $excludeTypes ) );
		}
		if ( empty( $indices ) ) {
			// If indices to search in are empty, it will search in ALL indices available on server
			// which is a nuisance at best, and security issue at worst, in farms and shared OS instances.
			// Should never happen, but this is a safety net.
			return new SearchResultSet( [], $this );
		}

		$query = $lookup->getQueryDSL();
		if ( isset( $query['indices_boost'] ) ) {
			$replaced = [];
			foreach ( $query['indices_boost'] as $boost ) {
				foreach ( $boost as $index => $boostFactor ) {
					// Do source type to index name conversion
					$replaced[$this->getIndexName( $index )] = $boostFactor;
				}
			}
			$query['indices_boost'] = $replaced;
		}

		return $this->runRawQueryFromData( [
			'index' => implode( ',', $indices ),
			'body' => $query,
			'_source' => $query['_source'] ?? [],
			'from' => $lookup->getFrom(),
			'size' => $lookup->getSize(),
		] );
	}

	/**
	 * @param array $data
	 *
	 * @return SearchResultSet
	 */
	public function runRawQueryFromData( array $data ): SearchResultSet {
		$raw = $this->getClient()->search( $data );
		wfDebugLog( 'BSExtendedSearch', 'Raw Query: ' . FormatJson::encode( $data, true ) );
		wfDebugLog( 'BSExtendedSearch', 'Raw Result: ' . FormatJson::encode( $raw, true ) );
		return new SearchResultSet( $raw, $this );
	}

	/**
	 * Runs query against OpenSearch and formats returned values
	 *
	 * @param Lookup $lookup
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public function runLookup( $lookup ) {
		$origQS = $lookup->getQueryString();
		$origTerm = $origQS['query'];

		$lookupModifiers = $this->getLookupModifiers( $lookup, static::QUERY_TYPE_SEARCH );
		foreach ( $lookupModifiers as $lookupModifier ) {
			$lookupModifier->apply();
		}

		wfDebugLog(
			'BSExtendedSearch',
			'Query by ' . $this->getContext()->getUser()->getName() . ': '
			. FormatJson::encode( $lookup, true )
		);
		try {
			$spellcheck = $this->spellCheck( $lookup, $origTerm );
			$results = $this->runRawQuery( $lookup );
		} catch ( Throwable $ex ) {
			wfDebugLog(
				'BSExtendedSearch',
				__METHOD__ . " error: {$ex->getMessage()}"
			);

			$ret = new \stdClass();
			// we cannot return anything else other than just exception type,
			// because any exception message may contain
			// full query, and therefore, sensitive data
			$ret->exception = true;
			$ret->exceptionType = get_class( $ex );
			return $ret;
		}

		foreach ( $lookupModifiers as $lookupModifier ) {
			$lookupModifier->undo();
		}

		// TODO: This can now possibly be retrieved from the results object
		$totalApproximated = $lookup->getSize() > $results->getTotalHits() ? false : true;

		$resultData = $results->getResults();
		$searchAfter = $this->getSearchAfterFromResults( $resultData );

		$postProcessor = $this->getPostProcessor( static::QUERY_TYPE_SEARCH );
		$postProcessor->process( $resultData, $lookup );
		$formattedResultSet = new stdClass();
		$formattedResultSet->results = $this->formatResults( $resultData, $lookup );
		$formattedResultSet->total = $results->getTotalHits();
		$formattedResultSet->filters = $this->getFilterConfig( $results );
		$formattedResultSet->search_after = $searchAfter;
		$formattedResultSet->spellcheck = $spellcheck;
		$formattedResultSet->total_approximated = $totalApproximated ? 1 : 0;

		if ( $this->isHistoryTrackingEnabled() ) {
			$searchHistoryInfo = [
				'user' => $this->getContext()->getUser()->getId(),
				'term' => $origTerm,
				'total' => $results->getTotalHits(),
				'total_approximated' => $totalApproximated,
				'lookup' => $lookup,
				'timestamp' => wfTimestamp( TS_MW ),
				'autocorrected' => false
			];

			if ( $spellcheck['action'] == static::SPELLCHECK_ACTION_REPLACED ) {
				$searchHistoryInfo['term'] = $spellcheck['alternative']['term'];
				$searchHistoryInfo['autocorrected'] = true;
			}

			$this->logSearchHistory( $searchHistoryInfo );
		}

		return $formattedResultSet;
	}

	/**
	 * Checks if there are alternatives to what user is searching for
	 * and replaces the term if it detects a typo
	 *
	 * Note: Revisit for final version, this is prototype-y
	 * TODO: Implement multi-field suggestions
	 *
	 * @param Lookup $lookup
	 * @param string $origTerm
	 * @return array
	 */
	public function spellCheck( $lookup, $origTerm ) {
		$spellcheckResult = [
			"action" => static::SPELLCHECK_ACTION_IGNORE
		];
		if ( $lookup->getSearchAfter() ) {
			return $spellcheckResult;
		}

		if ( $lookup->getForceTerm() ) {
			$lookup->removeForceTerm();
			return $spellcheckResult;
		}

		// Do not spellcheck regex
		if ( strpos( $origTerm, '/' ) === 0 && substr( $origTerm, -1 ) === '/' ) {
			return $spellcheckResult;
		}

		if ( strpos( $origTerm, '*' ) !== false ) {
			return $spellcheckResult;
		}
		// Do not spellcheck quoted terms
		if ( preg_match( '/\".*?\"/', $origTerm ) ) {
			return $spellcheckResult;
		}

		$spellCheckConfig = $this->getSpellCheckConfig();
		if ( !( $spellCheckConfig['enabled'] ?? false ) ) {
			return $spellcheckResult;
		}

		$origTermLookup = $lookup;
		try {
			$query = $origTermLookup->getQueryDSL();
			unset( $query['from'] );
			unset( $query['size'] );
			unset( $query['sort'] );
			unset( $query['indices_boost'] );
			unset( $query['aggs'] );
			unset( $query['_source'] );
			unset( $query['highlight'] );
			$origHitCount = $this->getClient()->count( [
				'index' => implode( ',', $this->getAllIndicesForQuery() ),
				'body' => $query
			] );
			$origHitCount = $origHitCount['count'] ?? 0;
		} catch ( Exception $ex ) {
			return $spellcheckResult;
		}

		// What is our best alternative
		$suggestLookup = new Lookup();
		$suggestLookup->addSuggest( $spellCheckConfig['suggestField'], $origTerm );
		$suggestResults = $this->runRawQuery( $suggestLookup );
		$suggestedTerm = [];
		$suggestions = $suggestResults->getSuggest()['spell-check'];
		if ( empty( $suggestions ) ) {
			return $spellcheckResult;
		}
		foreach ( $suggestions as $suggestion ) {
			if ( count( $suggestion['options'] ) == 0 ) {
				// Word is already best it can be
				$suggestedTerm[] = $suggestion['text'];
			} else {
				// Get first ( highest scored ) alternative
				$suggestedTerm[] = $suggestion['options'][0]['text'];
			}
		}

		$suggestedTerm = implode( ' ', $suggestedTerm );
		if ( $suggestedTerm == $origTerm ) {
			return $spellcheckResult;
		}

		// How many results would our best alternative yield
		$suggestLookup = clone $origTermLookup;
		$suggestQueryString = $origTermLookup->getQueryString();
		$escapedTerm = preg_quote( $origTerm, '/' );
		$suggestQueryString['query'] = preg_replace(
			'/' . $escapedTerm . '/',
			$suggestedTerm,
			$suggestQueryString['query']
		);
		$suggestLookup->setQueryString( $suggestQueryString );
		$query = $suggestLookup->getQueryDSL();
		unset( $query['from'] );
		unset( $query['size'] );
		unset( $query['sort'] );
		unset( $query['indices_boost'] );
		unset( $query['aggs'] );
		unset( $query['_source'] );
		unset( $query['highlight'] );
		$suggestedHitCount = $this->getClient()->count( [
			'index' => implode( ',', $this->getAllIndicesForQuery() ),
			'body' => $query
		] );
		$suggestedHitCount = $suggestedHitCount['count'] ?? 0;

		// Decide if we will replace original term with suggested one
		if ( $suggestedHitCount <= $origHitCount ) {
			return $spellcheckResult;
		}

		$spellcheckResult['original'] = [
			"term" => $origTerm,
			"count" => $origHitCount
		];

		$spellcheckResult['alternative'] = [
			"term" => $suggestedTerm,
			"count" => $suggestedHitCount
		];

		$replace = false;
		if ( $origHitCount == 0 ) {
			$replace = true;
		} else {
			// How much more results we get using suggested term
			$percent = $origHitCount / $suggestedHitCount;
			if ( $percent < $spellCheckConfig['replaceThreshold'] ) {
				// Replace term if there is much more hits for alternative
				$replace = true;
			} elseif ( $percent < $spellCheckConfig['suggestThreshold'] ) {
				// If alternative has siginificatly more results, but not so much
				// that we can definitely decide its a typo, just suggest the alternative
				$spellcheckResult['action'] = static::SPELLCHECK_ACTION_SUGGEST;
			}
		}

		if ( $replace ) {
			$origQS['query'] = $suggestedTerm;
			$lookup->setQueryString( $origQS );

			$spellcheckResult['action'] = static::SPELLCHECK_ACTION_REPLACED;
		}

		return $spellcheckResult;
	}

	/**
	 * Runs each result in result set through
	 * each source's formatter
	 *
	 * @param SearchResult[] $results
	 * @param Lookup $lookup
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function formatResults( array $results, Lookup $lookup ): array {
		$formattedResults = [];

		foreach ( $results as $resultObject ) {
			$result = $resultObject->getData();
			foreach ( $this->getSources() as $source ) {
				$formatter = $source->getFormatter();
				$formatter->setLookup( $lookup );
				$formatter->format( $result, $resultObject );
				$plugins = $this->pluginManager->getPluginsImplementing( IFormattingModifier::class );
				/** @var IFormattingModifier $plugin */
				foreach ( $plugins as $plugin ) {
					$plugin->formatFulltextResult( $result, $resultObject, $source, $lookup );
				}
			}

			$formattedResults[] = $result;
		}

		return $formattedResults;
	}

	/**
	 *
	 * @param SearchResultSet $results
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getFilterConfig( $results ) {
		// Fields that have "AND/OR" option enabled. Would be better if this could
		// be retrieved from mapping, but since ES assigns types dynamically, it's not possible.
		// It could also be inferred from results, but we need filter cfg even when no
		// results are retrieved. Basically, this are all the fields of type array
		$fieldsWithANDEnabled = ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchFieldsWithANDFilterEnabled' );

		// Filters that can only have one option selected at a time
		$singleSelectFitlers = ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSingleSelectFilters' );

		$aggs = $results->getAggregations();

		$filterCfg = [];
		// Ultimately, the Base formatter should handle this, but for now its here
		foreach ( $aggs as $filterName => $agg ) {
			if ( empty( $agg['buckets'] ) ) {
				continue;
			}
			$fieldName = substr( $filterName, 6 );
			$filterCfg[$fieldName] = [
				'buckets' => $agg['buckets'],
				'isANDEnabled' => 0,
				'multiSelect' => 1
			];
			if ( in_array( $fieldName, $fieldsWithANDEnabled['fields'] ) ) {
				$filterCfg[$fieldName]['isANDEnabled'] = 1;
			}
			if ( in_array( $fieldName, $singleSelectFitlers ) ) {
				$filterCfg[$fieldName]['multiSelect'] = 0;
			}
		}

		// Let sources modify the filters if needed
		foreach ( $this->getSources() as $source ) {
			$formatter = $source->getFormatter();
			$formatter->formatFilters( $aggs, $filterCfg, $fieldsWithANDEnabled );
			$plugins = $this->pluginManager->getPluginsImplementing( IFilterModifier::class );
			/** @var IFilterModifier $plugin */
			foreach ( $plugins as $plugin ) {
				$plugin->modifyFilters( $aggs, $filterCfg, $fieldsWithANDEnabled, $source );
			}
		}

		return $filterCfg;
	}

	/**
	 * Gets predefined result structure from attribute
	 *
	 * @return array
	 */
	public function getDefaultResultStructure() {
		$defaultStructure = ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchDefaultResultStructure' );

		return $defaultStructure;
	}

	/**
	 * Gets settings for autocomplete
	 *
	 * @return array
	 */
	public function getAutocompleteConfig() {
		$autocompleteConfig = ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );

		return $autocompleteConfig;
	}

	/**
	 *
	 * @return array
	 */
	public function getSpellCheckConfig() {
		/** @var ManifestRegistryFactory $factory */
		$factory = $this->getService( 'MWStakeManifestRegistryFactory' );
		$registry = $factory->get( 'BlueSpiceExtendedSearchSpellCheck' );
		return $registry->getAllValues();
	}

	/**
	 * Returns service object for the given name
	 * or null if service does not exist or is disabled
	 *
	 * @param string $name
	 * @phpcs:ignore MediaWiki.Commenting.FunctionComment.ObjectTypeHintReturn
	 * @return object|null
	 */
	public function getService( $name ) {
		if ( MediaWikiServices::getInstance()->hasService( $name ) ) {
			return MediaWikiServices::getInstance()->getService( $name );
		}
		return null;
	}

	/**
	 *
	 * @return bool
	 */
	protected function isHistoryTrackingEnabled() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return $config->get( 'ESEnableSearchHistoryTracking' );
	}

	/**
	 *
	 * @param array $data
	 */
	protected function logSearchHistory( $data ) {
		$dbw = $this->lb->getConnection( DB_PRIMARY );

		$dbw->insert(
			'bs_extendedsearch_history',
			[
				'esh_user' => $data['user'],
				'esh_term' => strtolower( $data['term'] ),
				'esh_hits' => $data['total'],
				'esh_hits_approximated' => $data['total_approximated'] ? 1 : 0,
				'esh_timestamp' => $data['timestamp'],
				'esh_autocorrected' => $data['autocorrected'] ? 1 : 0,
				'esh_lookup' => serialize( $data['lookup'] )
			],
			__METHOD__
		);
	}

	/**
	 * @param string $searchType
	 * @return PostProcessor
	 * @throws Exception
	 */
	private function getPostProcessor( $searchType ) {
		$backend = $this;
		return PostProcessor::factory( $searchType, $backend );
	}

	/**
	 * Get all indices
	 *
	 * @param array|null $limitToSources
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getAllIndicesForQuery( ?array $limitToSources = null ): array {
		$indices = [];
		foreach ( $this->getSources() as $key => $source ) {
			if ( $limitToSources !== null && !in_array( $key, $limitToSources ) ) {
				continue;
			}
			$indices[] = $this->getIndexName( $key );
		}
		$indexProviders = $this->pluginManager->getPluginsImplementing( IIndexProvider::class );
		foreach ( $indexProviders as $indexProvider ) {
			$indexProvider->setIndices( $this, $limitToSources, $indices );
		}
		return $indices;
	}

	/**
	 * @param string $index
	 *
	 * @return bool
	 */
	public function isForeignIndex( string $index ): bool {
		$prefix = $this->getConfig()->get( 'index' ) . '_';
		return !str_starts_with( $index, $prefix );
	}

	/**
	 * @param string $index
	 *
	 * @return string
	 */
	public function typeFromIndexName( string $index ): string {
		$plugins = $this->pluginManager->getPluginsImplementing( IIndexProvider::class );
		/** @var IIndexProvider $plugin */
		foreach ( $plugins as $plugin ) {
			$pluginIndexType = $plugin->typeFromIndexName( $index, $this );
			if ( $pluginIndexType ) {
				return $pluginIndexType;
			}
		}
		$prefix = $this->getConfig()->get( 'index' ) . '_';
		if ( str_starts_with( $index, $prefix ) ) {
			return substr( $index, strlen( $prefix ) );
		}
		return $index;
	}

	/**
	 * @param string $class
	 *
	 * @return ISearchPlugin[]
	 */
	public function getPluginsForInterface( string $class ): array {
		return $this->pluginManager->getPluginsImplementing( $class );
	}

	/**
	 * @param array $rawResultData
	 * @return array
	 */
	private function getSearchAfterFromResults( array $rawResultData ): array {
		/** @var SearchResult $lastResult */
		$lastResult = end( $rawResultData );
		if ( !$lastResult ) {
			return [];
		}

		$sort = $lastResult->getSort();
		if ( !$sort ) {
			return [];
		}
		return $sort;
	}

}
