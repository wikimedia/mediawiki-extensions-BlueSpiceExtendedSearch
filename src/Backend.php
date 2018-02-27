<?php

namespace BS\ExtendedSearch;

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

		foreach( $backendConfigs as $backendKey => $aBackendConfig ) {
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
			throw new Exception( "BACKEND: Key '$backendKey' not set in config!" );
		}

		return $backendConfigs[$backendKey];
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
		$indexSettings = [];
		foreach( $this->sources as $source ) {
			$indexSettings = array_merge_recursive(
				$indexSettings,
				$source->getIndexSettings()
			);
		}

		foreach( $this->sources as $source ) {
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

	/**
	 *
	 * @param Lookup $lookup
	 */
	public function runLookup( $lookup ) {
		$context = \RequestContext::getMain();
		$lookupModifiers = [];
		$types = $lookup->getTypes();
		foreach( $this->sources as $sourceKey => $source ) {
			if( !empty( $types ) && !in_array( $sourceKey, $types ) ) {
				continue;
			}
			$lookupModifiers += $source->getLookupModifiers( $lookup, $context );
		}

		foreach( $lookupModifiers as $sLMKey => $lookupModifier ) {
			$lookupModifier->apply();
		}

		wfDebugLog(
			'BSExtendedSearch',
			'Query by '.$context->getUser()->getName().': '
				.\FormatJson::encode( $lookup, true )
		);

		$search = new \Elastica\Search( $this->getClient() );

		$results = $search->search( $lookup->getQueryDSL() );

		return $results;

		//TODO: Implement
	}
}