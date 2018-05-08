<?php

namespace BS\ExtendedSearch;

use MediaWiki\MediaWikiServices;
use BS\ExtendedSearch\Source\LookupModifier\Base as LookupModifier;

class Backend {

	/**
	 *
	 * @var Config
	 */
	protected $config = null;

	/**
	 *
	 * @var Source\Base[]
	 */
	protected $sources = [];

	/**
	 *
	 * @var \Elastica\Client
	 */
	protected $client = null;

	public function __construct( $aConfig ) {
		if( !isset( $aConfig['index'] ) ) {
			$aConfig['index'] = wfWikiID();
		}

		$this->config = new \HashConfig( $aConfig );
	}

	/**
	 *
	 * @param string $sourceKey
	 * @return Source\Base
	 * @throws \Exception
	 */
	public function getSource( $sourceKey ) {
		if( isset( $this->sources[$sourceKey] ) ) {
			return $this->sources[$sourceKey];
		}

		$sourceConfigs = $this->config->get( 'sources' );
		if( !isset( $sourceConfigs[$sourceKey] ) ) {
			throw new \Exception( "SOURCE: Key '$sourceKey' not set in config!" );
		}

		//Decorator!
		$oBaseSourceArgs = [[]]; //Yes, array-in-an-array
		if( isset( $sourceConfigs[$sourceKey]['args'] ) ) {
			$oBaseSourceArgs = $sourceConfigs[$sourceKey]['args'];
		}

		if( !isset( $oBaseSourceArgs[0]['sourcekey'] ) ) {
			$oBaseSourceArgs[0]['sourcekey'] = $sourceKey;
		}

		//Dependency injection of Backend into Source
		array_unshift ($oBaseSourceArgs, $this );

		$oBaseSource = \ObjectFactory::getObjectFromSpec( [
			'class' => 'BS\ExtendedSearch\Source\Base',
			'args' => $oBaseSourceArgs
		] );

		$oDecoratedSource = \ObjectFactory::getObjectFromSpec( [
			'class' => $sourceConfigs[$sourceKey]['class'],
			'args' => [ $oBaseSource ]
		] );

		\Hooks::run( 'BSExtendedSearchMakeSource', [ $this, $sourceKey, &$oDecoratedSource ] );

		$this->sources[$sourceKey] = $oDecoratedSource;

		return $this->sources[$sourceKey];
	}

	/**
	 *
	 * @return Source\Base[]
	 */
	public function getSources() {
		foreach( $this->config->get('sources') as $sourceKey => $sSourceConfig ) {
			$this->getSource( $sourceKey );
		}
		return $this->sources;
	}

	/**
	 *
	 * @return \Elastica\Client
	 */
	public function getClient() {
		if( $this->client === null ) {
			$this->client = new \Elastica\Client(
				$this->config->get( 'connection' )
			);
		}

		return $this->client;
	}

	/**
	 *
	 * @var Backend[]
	 */
	protected static $backends = [];

	/**
	 *
	 * @param string $backendKey
	 * @return Backend
	 */
	public static function instance( $backendKey ) {
		if( isset( self::$backends[$backendKey] ) ) {
			return self::$backends[$backendKey];
		}

		self::$backends[$backendKey] = self::newFromConfig(
			self::getConfigFromKey( $backendKey )
		);

		return self::$backends[$backendKey];
	}

	/**
	 *
	 * @param string $aConfig
	 */
	protected static function newFromConfig( $aConfig ) {
		return \ObjectFactory::getObjectFromSpec( $aConfig );
	}

	/**
	 *
	 * @return Backend[]
	 */
	public static function factoryAll() {
		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsgES' );
		$backendConfigs = $config->get( 'Backends' );

		foreach( $backendConfigs as $backendKey => $backendConfig ) {
			self::instance( $backendKey );
		}

		return self::$backends;
	}

	/**
	 *
	 * @param sting $backendKey
	 * @return array
	 * @throws Exception
	 */
	protected static function getConfigFromKey( $backendKey ) {
		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsgES' );
		$backendConfigs = $config->get( 'Backends' );

		if( !isset( $backendConfigs[$backendKey] ) ) {
			throw new \Exception( "BACKEND: Key '$backendKey' not set in config!" );
		}

		return $backendConfigs[$backendKey];
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
	 * @throws Elastica\Exception\ResponseException
	 */
	public function deleteIndexes() {
		foreach( $this->sources as $source ) {
			$sourceType = $source->getTypeKey();
			$index = $this->getIndexByType( $sourceType );
			if( $index->exists() ){
				$index->delete();
			}
		}
	}

	/**
	 * @throws Elastica\Exception\ResponseException
	 */
	public function createIndexes() {
		foreach( $this->sources as $source ) {
			$indexSettings = $source->getIndexSettings();

			$index = $this->getIndexByType( $source->getTypeKey() );
			$response = $index->create( $indexSettings );

			$type = $index->getType( $source->getTypeKey() );

			$mapping = new \Elastica\Type\Mapping();
			$mapping->setType( $type );
			$mappingProvider = $source->getMappingProvider();
			$mapping->setProperties( $mappingProvider->getPropertyConfig() );

			$sourceConfig = $mappingProvider->getSourceConfig();
			if( !empty( $sourceConfig ) ) {
				$mapping->setSource( $sourceConfig );
			}

			$response2 = $mapping->send( [
				//Neccessary if more than one type has a 'attachment' field from 'mapper-attachments'
				'update_all_types' => ''
			] );
		}
	}

	/**
	 *
	 * @return \Elastica\Index
	 */
	public function getIndexByType( $type ) {
		return $this->getClient()->getIndex( $this->config->get( 'index' ) . '_' . $type );
	}

	public function getContext() {
		return \RequestContext::getMain();
	}

	/**
	 * Runs quick query agains ElasticSearch
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 * @return array
	 */
	public function runAutocompleteLookup( Lookup $lookup, $searchData ) {
		$acConfig = $this->getAutocompleteConfig();

		$search = new \Elastica\Search( $this->getClient() );
		$search->addIndex( $this->config->get( 'index' ) . '_*' );

		$results = [];
		$lookupModifiers = [];
		foreach( $this->sources as $sourceKey => $source ) {
			$lookupModifiers += $source->getLookupModifiers( $lookup, $this->getContext(), LookupModifier::TYPE_AUTOCOMPLETE );
		}

		foreach( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->apply();
		}

		$results = $search->search( $lookup->getQueryDSL() );
		$results = $this->formatQuerySuggestions( $results, $searchData );

		return $results;

	}

	protected function formatQuerySuggestions( $results, $searchData ) {
		$results = array_values( $this->getQuerySuggestionList( $results ) );
		return $this->formatSuggestions( $results, $searchData );
	}

	protected function formatSuggestions( $results, $searchData ) {
		$lcSearchTerm = strtolower( $searchData['value'] );

		foreach( $this->getSources() as $sourceKey => $source ) {
			$source->getFormatter()->scoreAutocompleteResults( $results, $searchData );
			$source->getFormatter()->rankAutocompleteResults( $results, $searchData );
			//when results are ranked based on original data, it can be modified
			$source->getFormatter()->formatAutocompleteResults( $results, $searchData );
		}

		usort( $results, function( $e1, $e2 ) {
			if( $e1['score'] == $e2['score'] ) {
				return 0;
			}
			return ( $e1['score'] < $e2['score'] ) ? 1 : -1;
		} );

		return $results;
	}

	protected function getQuerySuggestionList( $results ) {
		$res = [];
		foreach( $results->getResults() as $suggestion ) {
			$item = [
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
	 */
	public function runLookup( $lookup ) {
		$search = new \Elastica\Search( $this->getClient() );
		$search->addIndex( $this->config->get( 'index' ) . '_*' );

		$spellcheck = $this->spellCheck( $lookup, $search );

		$lookupModifiers = [];
		foreach( $this->sources as $sourceKey => $source ) {
			$lookupModifiers += $source->getLookupModifiers( $lookup, $this->getContext(), LookupModifier::TYPE_SEARCH );
		}

		foreach( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->apply();
		}

		wfDebugLog(
			'BSExtendedSearch',
			'Query by ' . $this->getContext()->getUser()->getName() . ': '
				. \FormatJson::encode( $lookup, true )
		);

		try {
			$results = $search->search( $lookup->getQueryDSL() );
		} catch( \RuntimeException $ex ) {
			$ret = new \stdClass();
			//we cannot return anything else other than just exception type,
			//because any exception message may contain
			//full query, and therefore, sensitive data
			$ret->exception = true;
			$ret->exceptionType = get_class( $ex );
			return $ret;
		}

		foreach( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->undo();
		}

		$formattedResultSet = new \stdClass();
		$formattedResultSet->results = $this->formatResults( $results );
		$formattedResultSet->total = $this->getTotal( $results );
		$formattedResultSet->filters = $this->getFilterConfig( $results );
		$formattedResultSet->spellcheck = $spellcheck;

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
	 * @param \Elastica\Search $search
	 * @return array
	 */
	public function spellCheck( &$lookup, $search ) {
		$spellcheckResult = [
			"action" => "ignore"
		];

		if( $lookup->getForceTerm() ) {
			$lookup->removeForceTerm();
			return $spellcheckResult;
		}
		$spellCheckConfig = $this->getSpellCheckConfig();
		//How many hits would we have with search term as-is
		$origQS = $lookup->getQueryString();
		$origTerm = $origQS['query'];

		$origTermLookup = new Lookup();
		$origTermLookup->setQueryString( $origTerm );
		$origHitCount = $search->count( $origTermLookup->getQueryDSL() );

		//What is our best alternative
		$suggestLookup = new Lookup();
		$suggestLookup->addSuggest( $spellCheckConfig['suggestField'], $origTerm );
		$suggestResults = $search->search( ['suggest' => $suggestLookup->getQueryDSL() ] );

		$suggestedTerm = [];
		$suggestions = $suggestResults->getSuggests()[$spellCheckConfig['suggestField']];
		foreach( $suggestions as $suggestion ) {
			if( count( $suggestion['options'] ) == 0 ) {
				//Word is already best it can be
				$suggestedTerm[] = $suggestion['text'];
			} else {
				//Get first ( highest scored ) alternative
				$suggestedTerm[] = $suggestion['options'][0]['text'];
			}
		}

		$suggestedTerm = implode( ' ', $suggestedTerm );

		if( $suggestedTerm == $origTerm ) {
			return $spellcheckResult;
		}

		//How many results would our best alternative yield
		$suggestLookup = new Lookup();
		$suggestLookup->setQueryString( $suggestedTerm );
		$suggestedHitCount = $search->count( $suggestLookup->getQueryDSL() );

		//Decide if we will replace original term with suggested one
		if( $suggestedHitCount <= $origHitCount ) {
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
		if( $origHitCount == 0 ) {
			$replace = true;
		} else {
			//How much more results we get using suggested term
			$percent = $origHitCount / $suggestedHitCount;
			if( $percent < $spellCheckConfig['replaceThreshold'] ) {
				//Replace term if there is much more hits for alternative
				$replace = true;
			} else if ( $percent < $spellCheckConfig['suggestThreshold'] ) {
				//If alternative has siginificatly more results, but not so much
				//that we can definitely decide its a typo, just suggest the alternative
				$spellcheckResult['action'] = 'suggest';
			}
		}

		if( $replace ) {
			$origQS['query'] = $suggestedTerm;
			$lookup->setQueryString( $origQS );

			$spellcheckResult['action'] = 'replaced';
		}

		return $spellcheckResult;
	}
	/**
	 * Runs each result in result set through
	 * each source's formatter
	 *
	 * @param \Elastica\ResultSet $results
	 */
	protected function formatResults( $results ) {
		$formattedResults = [];

		foreach( $results->getResults() as $resultObject ) {
			$result = $resultObject->getData();
			foreach( $this->getSources() as $sourceKey => $source ) {
				$source->getFormatter()->format( $result, $resultObject );
			}
			$formattedResults[] = $result;
		}

		return $formattedResults;
	}

	/**
	 *
	 * @param \Elastica\ResultSet $results
	 */
	protected function getTotal( $results ) {
		return $results->getTotalHits();
	}

	/**
	 *
	 * @param \Elastica\ResultSet $results
	 */
	protected function getFilterConfig( $results ) {
		//Fields that have "AND/OR" option enabled. Would be better if this could
		//be retrieved from mapping, but since ES assigns types dinamically, not possible.
		//It could also be infered from results, but we need filter cfg even when no
		//results are retrieved. Basically, this are all the fields of type array
		$fieldsWithANDEnabled = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchFieldsWithANDFilterEnabled' );

		$aggs = $results->getAggregations();
		$filterCfg = [];
		foreach( $aggs as $filterName => $agg ) {
			$fieldName = substr( $filterName, 6 );
			$filterCfg[$fieldName] = [
				'buckets' => $agg['buckets'],
				'isANDEnabled' => 0
			];
			if( in_array( $fieldName, $fieldsWithANDEnabled['fields'] ) ) {
				$filterCfg[$fieldName]['isANDEnabled'] = 1;
			}
		}

		return $filterCfg;
	}

	/**
	 * Gets predifined result structure from attribute
	 *
	 * @returns array
	 */
	public function getDefaultResultStructure() {
		$defaultStructure = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchDefaultResultStructure' );

		return $defaultStructure;
	}

	/**
	 * Gets settings for autocomplete
	 *
	 * @returns array
	 */
	public function getAutocompleteConfig() {
		$autocompleteConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );

		return $autocompleteConfig;
	}

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
		if( MediaWikiServices::getInstance()->hasService( $name ) ) {
			return MediaWikiServices::getInstance()->getService( $name );
		}
		return null;
	}
}