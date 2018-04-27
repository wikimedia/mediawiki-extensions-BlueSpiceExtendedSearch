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
		$strategy = $acConfig['SuggestStrategy'];

		$search = new \Elastica\Search( $this->getClient() );
		$search->addIndex( $this->config->get( 'index' ) . '_*' );

		$results = [];
		if( $strategy == Lookup::AC_STRATEGY_QUERY ) {
			$lookupModifiers = [];
			foreach( $this->sources as $sourceKey => $source ) {
				$lookupModifiers += $source->getLookupModifiers( $lookup, $this->getContext(), LookupModifier::TYPE_AUTOCOMPLETE );
			}

			foreach( $lookupModifiers as $sLMKey => $lookupModifier ) {
				$lookupModifier->apply();
			}

			$results = $search->search( $lookup->getQueryDSL() );
			$results = $this->formatQuerySuggestions( $results, $searchData );
		} else {
			if( empty( $lookup->getAutocompleteSuggest() ) ) {
				return [];
			}
			$results = $search->search( $lookup->getAutocompleteSuggestQuery() );
			$results = $this->formatCompletionSuggestions( $results, $searchData );
		}

		return $results;

	}

	protected function formatQuerySuggestions( $results, $searchData ) {
		$results = array_values( $this->getQuerySuggestionList( $results ) );
		return $this->formatSuggestions( $results, $searchData );
	}

	protected function formatCompletionSuggestions( $results, $searchData ) {
		$results = array_values( $this->getCompletionSuggestionList( $results ) );
		return $this->formatSuggestions( $results, $searchData );
	}

	protected function formatSuggestions( $results, $searchData ) {
		$lcSearchTerm = strtolower( $searchData['value'] );

		foreach( $this->getSources() as $sourceKey => $source ) {
			$source->getFormatter()->scoreAutocompleteResults( $results, $searchData );
			//when results are scored based on original data, it can be modified
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
				"is_scored" => false
			];

			$item = array_merge( $item, $suggestion->getSource() );

			$res[$suggestion->getId()] = $item;
		}

		return $res;
	}

	protected function getCompletionSuggestionList( $results ) {
		$res = [];
		foreach( $results->getSuggests() as $suggestionField => $suggestion ) {
			foreach( $suggestion[0]['options'] as $option ) {
				$item = [
					"type" => $option['_type'],
					"score" => $option['_score'],
					"is_scored" => false
				];

				$item = array_merge( $item, $option['_source'] );

				$res[$option['_id']] = $item;
			}
		}

		return $res;
	}

	/**
	 * Runs query against ElasticSearch and formats returned values
	 *
	 * @param Lookup $lookup
	 */
	public function runLookup( $lookup ) {
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

		$search = new \Elastica\Search( $this->getClient() );
		$search->addIndex( $this->config->get( 'index' ) . '_*' );

		$results = $search->search( $lookup->getQueryDSL() );

		foreach( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->undo();
		}

		$formattedResultSet = new \stdClass();
		$formattedResultSet->results = $this->formatResults( $results );
		$formattedResultSet->total = $this->getTotal( $results );
		$formattedResultSet->filters = $this->getFilterConfig( $results );

		return $formattedResultSet;
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