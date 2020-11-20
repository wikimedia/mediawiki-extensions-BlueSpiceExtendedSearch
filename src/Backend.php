<?php

namespace BS\ExtendedSearch;

use RequestContext;
use Hooks;
use Exception;
use MWException;
use stdClass;
use FormatJson;
use BS\ExtendedSearch\Source\WikiPages;
use Elastica\Exception\ResponseException;
use MediaWiki\MediaWikiServices;
use BS\ExtendedSearch\Source\LookupModifier\Base as LookupModifier;
use BS\ExtendedSearch\Source\Base as SourceBase;
use Elastica\Client;
use Elastica\Search;
use Elastica\Index;
use Elastica\ResultSet;

class Backend {
	const SPELLCHECK_ACTION_IGNORE = 'ignore';
	const SPELLCHECK_ACTION_SUGGEST = 'suggest';
	const SPELLCHECK_ACTION_REPLACED = 'replaced';

	const QUERY_TYPE_SEARCH = 'search';
	const QUERY_TYPE_AUTOCOMPLETE = 'autocomplete';

	/**
	 *
	 * @var Config
	 */
	protected $config = null;

	/**
	 *
	 * @var \Wikimedia\Rdbms\LoadBalancer
	 */
	protected $lb = null;

	/**
	 *
	 * @var Config
	 */
	protected $legacyConfig = null;

	/**
	 *
	 * @var SourceFactory
	 */
	protected $sourceFactory = null;

	/**
	 *
	 * @var LookupModifierFactory
	 */
	protected $lookupModifierFactory = null;

	/**
	 *
	 * @var SourceBase[]
	 */
	protected $sources = [];

	/**
	 *
	 * @var Client
	 */
	protected $client = null;

	/**
	 * @param Config $config
	 * @param \Wikimedia\Rdbms\LoadBalancer $lb
	 * @param SourceFactory $sourceFactory
	 * @param LookupModifierFactory $lookupModifierFactory
	 * @param array $legacyConfig
	 */
	public function __construct( $config, $lb, $sourceFactory, $lookupModifierFactory,
		array $legacyConfig = [] ) {
		if ( !isset( $legacyConfig['index'] ) ) {
			$legacyConfig['index'] = strtolower( wfWikiID() );
		}

		$this->legacyConfig = new \HashConfig( $legacyConfig );
		$this->config = new \MultiConfig( [ $config, $this->legacyConfig ] );
		$this->lb = $lb;
		$this->sourceFactory = $sourceFactory;
		$this->lookupModifierFactory = $lookupModifierFactory;
	}

	/**
	 *
	 * @param string $sourceKey
	 * @return SourceBase
	 * @throws Exception
	 */
	public function getSource( $sourceKey ) {
		$source = $this->sourceFactory->makeSource( $sourceKey, $this );
		Hooks::run( 'BSExtendedSearchMakeSource', [ $this, $sourceKey, &$source ] );

		$this->sources[$sourceKey] = $source;

		return $this->sources[$sourceKey];
	}

	/**
	 * @param string $sourceKey
	 */
	public function destroySource( $sourceKey ) {
		unset( $this->sources[$sourceKey] );
		$this->sourceFactory->destroySource( $sourceKey );	}

	/**
	 *
	 * @return SourceBase[]
	 */
	public function getSources() {
		foreach ( $this->legacyConfig->get( 'sources' ) as $sourceKey ) {
			$this->getSource( $sourceKey );
		}
		return $this->sources;
	}

	/**
	 * TODO: ClientFactory!
	 * @return Client
	 */
	public function getClient() {
		if ( $this->client === null ) {
			$backendHost = $this->getConfig()->get( 'ESBackendHost' );
			$backendPort = $this->getConfig()->get( 'ESBackendPort' );
			$backendTransport = $this->getConfig()->get( 'ESBackendTransport' );
			$this->client = new Client( [
				'host' => $backendHost,
				'port' => $backendPort,
				'transport' => $backendTransport
			] );
		}

		return $this->client;
	}

	/**
	 * @deprecated since version 3.1.13 - use service BSExtendedSearchBackend instead
	 * @return Backend
	 */
	public static function instance() {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		return MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
	}

	/**
	 * Deletes all indexes of all types existing
	 * for this index prefix
	 *
	 * DELETE /wiki_id_*
	 */
	public function deleteAllIndexes() {
		$indexes = $this->getIndexByType( '*' );
		$indexes->delete();
	}

	/**
	 * @throws MWException
	 */
	public function deleteIndexes() {
		foreach ( $this->sources as $source ) {
			$sourceKey = $source->getTypeKey();
			$this->deleteIndex( $sourceKey );
		}
	}

	/**
	 * Deletes all indexes
	 *
	 * @param string $sourceKey
	 * @throws ResponseException
	 */
	public function deleteIndex( $sourceKey ) {
		$index = $this->getIndexByType( $sourceKey );
		if ( $index->exists() ) {
			$index->delete();
		}
	}

	/**
	 * Creates all indexes
	 *
	 * @throws MWException
	 */
	public function createIndexes() {
		foreach ( $this->sources as $key => $source ) {
			$this->createIndex( $key );
		}
	}

	/**
	 * @param string $sourceKey
	 * @throws MWException
	 * @throws ResponseException
	 */
	public function createIndex( $sourceKey ) {
		if ( !isset( $this->sources[$sourceKey] ) ) {
			throw new MWException( "Source \"$sourceKey\" does not exist!" );
		}
		$source = $this->sources[$sourceKey];
		$indexSettings = $source->getIndexSettings();

		$index = $this->getIndexByType( $source->getTypeKey() );
		$response = $index->create( $indexSettings );

		$type = $index->getType( $source->getTypeKey() );

		$mapping = new \Elastica\Type\Mapping();
		$mapping->setType( $type );
		$mappingProvider = $source->getMappingProvider();
		$mapping->setProperties( $mappingProvider->getPropertyConfig() );

		$sourceConfig = $mappingProvider->getSourceConfig();
		if ( !empty( $sourceConfig ) ) {
			$mapping->setSource( $sourceConfig );
		}

		$response2 = $mapping->send( [
			// Necessary if more than one type has a 'attachment' field from 'mapper-attachments'
			'update_all_types' => ''
		] );

		$source->runAdditionalSetupRequests( $this->getClient() );
	}

	/**
	 *
	 * @param string $type
	 * @return Index
	 */
	public function getIndexByType( $type ) {
		return $this->getClient()->getIndex( $this->getConfig()->get( 'index' ) . '_' . $type );
	}

	/**
	 *
	 * @return \IContextSource
	 */
	public function getContext() {
		return RequestContext::getMain();
	}

	/**
	 *
	 * @return \Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Collects all the lookup modifiers for particular search type
	 *
	 * @param Lookup $lookup
	 * @param string $type
	 * @return array|LookupModifier[]
	 */
	public function getLookupModifiers( $lookup, $type ) {
		$lookupModifiers = $this->lookupModifierFactory->getLookupModifiersForQueryType(
			$type,
			$lookup,
			$this->getContext()
		);
		$lookupModifiers = array_merge(
			$lookupModifiers,
			$this->getLegacyLookupModifiers( $lookup, $type )
		);

		uasort( $lookupModifiers, function ( $a, $b ) {
			if ( $a->getPriority() === $b->getPriority() ) {
				return 0;
			}
			return ( $a->getPriority() > $b->getPriority() ) ? 1 : -1;
		} );

		return $lookupModifiers;
	}

	/**
	 * @deprecated since version 3.1.13 - Use registry instead and implement
	 * ILookupModifier.
	 * @param Lookup $lookup
	 * @param string $type
	 * @return LookupModifier[]
	 */
	private function getLegacyLookupModifiers( $lookup, $type ) {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		$lookupModifiers = [];
		foreach ( $this->sources as $sourceKey => $source ) {
			$lookupModifiers += $source->getLookupModifiers(
				$lookup,
				$this->getContext(),
				$type
			);
		}
		return $lookupModifiers;
	}

	/**
	 * Runs quick query agains ElasticSearch
	 *
	 * @param Lookup $lookup
	 * @param array $searchData
	 * @return array
	 */
	public function runAutocompleteLookup( Lookup $lookup, $searchData ) {
		$search = new Search( $this->getClient() );
		$this->addAllIndexesForQuery( $search );

		$lookupModifiers = $this->getLookupModifiers( $lookup, static::QUERY_TYPE_AUTOCOMPLETE );
		foreach ( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->apply();
		}

		$results = $search->search( $lookup->getQueryDSL() );

		$resultData = $results->getResults();
		$postProcessor = $this->getPostProcessor( static::QUERY_TYPE_AUTOCOMPLETE );
		$postProcessor->process( $resultData, $lookup );

		$results = $this->formatQuerySuggestions( $resultData, $searchData );

		return $results;
	}

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 * @param array $searchData
	 * @param array $secondaryRequestData
	 * @return array
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
	 * @return array
	 */
	protected function formatQuerySuggestions( $resultData, $searchData ) {
		$results = array_values( $this->getQuerySuggestionList( $resultData ) );
		return $this->formatSuggestions( $results, $searchData );
	}

	/**
	 *
	 * @param array $results
	 * @param array $searchData
	 * @return array
	 */
	protected function formatSuggestions( $results, $searchData ) {
		$searchData['value'] = strtolower( $searchData['value'] );

		foreach ( $this->getSources() as $sourceKey => $source ) {
			$source->getFormatter()->rankAutocompleteResults( $results, $searchData );
			// when results are ranked based on original data, it can be modified
			$source->getFormatter()->formatAutocompleteResults( $results, $searchData );
		}

		usort( $results, function ( $e1, $e2 ) {
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
				"type" => $suggestion->getType(),
				"score" => $suggestion->getScore(),
				"rank" => false,
				"is_ranked" => false
			];

			$item = array_merge( $item, $suggestion->getSource() );

			$res[$suggestion->getId()] = $item;
		}

		return $res;
	}

	/**
	 * Runs query against ElasticSearch and formats returned values
	 *
	 * @param Lookup $lookup
	 * @return stdClass
	 */
	public function runLookup( $lookup ) {
		$search = new Search( $this->getClient() );
		$this->addAllIndexesForQuery( $search );

		$origQS = $lookup->getQueryString();
		$origTerm = $origQS['query'];

		$lookupModifiers = $this->getLookupModifiers( $lookup, static::QUERY_TYPE_SEARCH );
		foreach ( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->apply();
		}

		wfDebugLog(
			'BSExtendedSearch',
			'Query by ' . $this->getContext()->getUser()->getName() . ': '
				. FormatJson::encode( $lookup, true )
		);

		try {
			$spellcheck = $this->spellCheck( $lookup, $search, $origTerm );
			$results = $search->search( $lookup->getQueryDSL() );
		} catch ( \RuntimeException $ex ) {
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

		foreach ( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->undo();
		}

		$totalApproximated = $lookup->getSize() > $this->getTotal( $results ) ? false : true;

		$resultData = $results->getResults();
		$postProcessor = $this->getPostProcessor( static::QUERY_TYPE_SEARCH );
		$postProcessor->process( $resultData, $lookup );

		$formattedResultSet = new stdClass();
		$formattedResultSet->results = $this->formatResults( $resultData, $lookup );
		$formattedResultSet->total = $this->getTotal( $results );
		$formattedResultSet->filters = $this->getFilterConfig( $results );
		$formattedResultSet->spellcheck = $spellcheck;
		$formattedResultSet->total_approximated = $totalApproximated ? 1 : 0;

		if ( $this->isHistoryTrackingEnabled() ) {
			$searchHistoryInfo = [
				'user' => $this->getContext()->getUser()->getId(),
				'term' => $origTerm,
				'total' => $this->getTotal( $results ),
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
	 * @param Search $search
	 * @param string $origTerm
	 * @return array
	 */
	public function spellCheck( $lookup, $search, $origTerm ) {
		$spellcheckResult = [
			"action" => static::SPELLCHECK_ACTION_IGNORE
		];

		// Do not spellcheck regex
		if ( strpos( $origTerm, '/' ) === 0 && substr( $origTerm, -1 ) === '/' ) {
			return $spellcheckResult;
		}
		if ( strpos( $origTerm, '*' ) !== false ) {
			return $spellcheckResult;
		}

		if ( $lookup->getForceTerm() ) {
			$lookup->removeForceTerm();
			return $spellcheckResult;
		}
		$spellCheckConfig = $this->getSpellCheckConfig();

		$origTermLookup = $lookup;
		$origHitCount = $search->count( $origTermLookup->getQueryDSL() );

		// What is our best alternative
		$suggestLookup = new Lookup();
		$suggestLookup->addSuggest( $spellCheckConfig['suggestField'], $origTerm );
		$suggestResults = $search->search( [ 'suggest' => $suggestLookup->getQueryDSL() ] );

		$suggestedTerm = [];
		$suggestions = $suggestResults->getSuggests()[$spellCheckConfig['suggestField']];

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
		$escapedOrigTerm = str_replace( '/', '\/', $origTerm );
		$suggestQueryString['query'] = preg_replace( '/' . $escapedOrigTerm . '/', $suggestedTerm, $suggestQueryString['query'] );
		$suggestLookup->setQueryString( $suggestQueryString );
		$suggestedHitCount = $search->count( $suggestLookup->getQueryDSL() );

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
	 * @param ResultSet $results
	 * @param Lookup $lookup
	 * @return array
	 */
	protected function formatResults( $results, $lookup ) {
		$formattedResults = [];

		foreach ( $results as $resultObject ) {
			$result = $resultObject->getData();
			foreach ( $this->getSources() as $sourceKey => $source ) {
				$formatter = $source->getFormatter();
				$formatter->setLookup( $lookup );
				$formatter->format( $result, $resultObject );
			}

			$formattedResults[] = $result;
		}

		return $formattedResults;
	}

	/**
	 *
	 * @param ResultSet $results
	 * @return int
	 */
	protected function getTotal( $results ) {
		return $results->getTotalHits();
	}

	/**
	 *
	 * @param ResultSet $results
	 * @return array
	 */
	protected function getFilterConfig( $results ) {
		// Fields that have "AND/OR" option enabled. Would be better if this could
		// be retrieved from mapping, but since ES assigns types dynamically, it's not possible.
		// It could also be inferred from results, but we need filter cfg even when no
		// results are retrieved. Basically, this are all the fields of type array
		$fieldsWithANDEnabled = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchFieldsWithANDFilterEnabled' );

		// Filters that can only have one option selected at a time
		$singleSelectFitlers = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSingleSelectFilters' );

		$aggs = $results->getAggregations();

		$filterCfg = [];

		// Let sources modify the filters if needed
		foreach ( $this->getSources() as $sourceKey => $source ) {
			$formatter = $source->getFormatter();
			$formatter->formatFilters( $aggs, $filterCfg, $fieldsWithANDEnabled );
		}

		// Ultimately, the Base formatter should handle this, but for now its here
		foreach ( $aggs as $filterName => $agg ) {
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

		return $filterCfg;
	}

	/**
	 * Gets predefined result structure from attribute
	 *
	 * @return array
	 */
	public function getDefaultResultStructure() {
		$defaultStructure = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchDefaultResultStructure' );

		return $defaultStructure;
	}

	/**
	 * Gets settings for autocomplete
	 *
	 * @return array
	 */
	public function getAutocompleteConfig() {
		$autocompleteConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );

		return $autocompleteConfig;
	}

	/**
	 *
	 * @return array
	 */
	public function getSpellCheckConfig() {
		$spellCheckConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSpellCheck' );

		return $spellCheckConfig;
	}

	/**
	 * Returns service object for the given name
	 * or null if service does not exist or is disabled
	 *
	 * @param string $name
	 * @return Object|null
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
		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsg' );
		return $config->get( 'ESEnableSearchHistoryTracking' );
	}

	/**
	 *
	 * @param array $data
	 */
	protected function logSearchHistory( $data ) {
		$dbw = $this->lb->getConnection( DB_MASTER );

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
			]
		);
	}

	/**
	 * Gets pages similar to given page
	 *
	 * TODO: Whether hard-coded values here should go to configs
	 * depends on fine-tuning, then we will know if it makes sense
	 *
	 * @param \Title $title
	 * @return array
	 */
	public function getSimilarPages( \Title $title ) {
		$wikipageSource = $this->getSource( 'wikipage' );
		if ( $wikipageSource instanceof WikiPages === false ) {
			return [];
		}

		// Searching "like" certain _id showed to yield best results
		$docId = $this->getDocIdForTitle( $title );
		if ( $docId === null ) {
			return [];
		}

		$index = $this->getIndexByType( $wikipageSource->getTypeKey() );
		$lookup = new Lookup();
		$lookup->setMLTQuery(
			$docId,
			[ 'prefixed_title', 'source_content' ],
			[
				// This is very important config. It is the minimal number of docs that need to be similar.
				// If it cannot find enough similar docs, it will return basically random results.
				"min_doc_freq" => 1
			],
			$index->getName()
		);
		$lookup->addSourceField( 'prefixed_title' );
		$lookup->setSize( 10 );

		$search = new \Elastica\Search( $this->getClient() );
		$search->addIndex( $index );
		$results = $search->search( $lookup->getQueryDSL() );

		$results = $results->getResults();
		$topScore = 0;
		foreach ( $results as $result ) {
			if ( $result->getScore() > $topScore ) {
				$topScore = $result->getScore();
			}
		}

		$pages = [];
		foreach ( $results as $result ) {
			$score = $result->getScore();
			if ( $topScore > 0 ) {
				$percentOfTopScore = $score * 100 / $topScore;
				if ( $percentOfTopScore < 50 ) {
					// Results that score less than 50% of top score
					// are usually useless
					continue;
				}
			}

			$data = $result->getData();
			$title = \Title::newFromText( $data['prefixed_title'] );
			if ( $title instanceof \Title === false || $title->exists() === false ) {
				continue;
			}
			$pages[$title->getPrefixedText()] = $title;
		}

		return $pages;
	}

	/**
	 * Get indexed _id of the given Title
	 *
	 * @param \Title $title
	 * @return string|null if page not indexed
	 */
	protected function getDocIdForTitle( \Title $title ) {
		$wikipageSource = $this->getSource( 'wikipage' );

		$search = new \Elastica\Search( $this->getClient() );
		$search->addIndex( $this->getIndexByType( $wikipageSource->getTypeKey() ) );

		$lookup = new Lookup( [
			"query" => [
				"term" => [
					"prefixed_title_exact" => $title->getPrefixedText()
				]
			]
		] );
		$lookup->setSize( 1 );
		$lookup->addSourceField( "prefixed_title" );

		$results = $search->search( $lookup->getQueryDSL() );

		if ( $results->count() === 0 ) {
			return null;
		}

		foreach ( $results->getResults() as $result ) {
			return $result->getId();
		}
	}

	private function getPostProcessor( $searchType ) {
		$backend = $this;
		return PostProcessor::factory( $searchType, $backend );
	}

	/**
	 * Add index for each source
	 *
	 * @param Search &$search
	 * @throws \ConfigException
	 */
	protected function addAllIndexesForQuery( Search &$search ) {
		foreach ( $this->getSources() as $key => $source ) {
			$search->addIndex( $this->getConfig()->get( 'index' ) . '_' . $key );
		}
	}
}
