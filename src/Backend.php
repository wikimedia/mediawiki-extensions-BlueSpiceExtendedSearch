<?php

namespace BS\ExtendedSearch;

class Backend {

	/**
	 *
	 * @var Config
	 */
	protected $oConfig = null;

	/**
	 *
	 * @var Source\Base[]
	 */
	protected $aSources = [];

	/**
	 *
	 * @var \Elastica\Client
	 */
	protected $oClient = null;

	public function __construct( $aConfig ) {
		$this->oConfig = new \HashConfig( $aConfig );
	}

	/**
	 *
	 * @param string $sSourceKey
	 * @return Source\Base
	 * @throws Exception
	 */
	public function getSource( $sSourceKey ) {
		if( isset( $this->aSources[$sSourceKey] ) ) {
			return $this->aSources[$sSourceKey];
		}

		$aSourceConfigs = $this->oConfig->get( 'sources' );
		if( !isset( $aSourceConfigs[$sSourceKey] ) ) {
			throw new Exception( "SOURCE: Key '$sSourceKey' not set in config!" );
		}

		//Decorator!
		$oBaseSourceArgs = [[]]; //Yes, array-in-a-array
		if( isset( $aSourceConfigs[$sSourceKey]['args'] ) ) {
			$oBaseSourceArgs = $aSourceConfigs[$sSourceKey]['args'];
		}
		$oBaseSource = \ObjectFactory::getObjectFromSpec( [
			'class' => 'BS\ExtendedSearch\Source\Base',
			'args' => $oBaseSourceArgs
		] );

		$oDecoratedSource = \ObjectFactory::getObjectFromSpec( [
			'class' => $aSourceConfigs[$sSourceKey]['class'],
			'args' => [ $oBaseSource ]
		] );

		\Hooks::run( 'BSExtendedSearchMakeSource', [ $this, $sSourceKey, &$oDecoratedSource ] );

		$this->aSources[$sSourceKey] = $oDecoratedSource;

		return $this->aSources[$sSourceKey];
	}

	/**
	 *
	 * @return Source\Base[]
	 */
	public function getSources() {
		foreach( $this->oConfig->get('sources') as $sSourceKey => $sSourceConfig ) {
			$this->getSource( $sSourceKey );
		}
		return $this->aSources;
	}

	public function getClient() {
		if( $this->oClient === null ) {
			$this->oClient = new \Elastica\Client(
				$this->oConfig->get( 'connection' )
			);
		}

		return $this->oClient;
	}

	/**
	 * @return
	 */
	public function getIndexManagers() {
		$sIndexName = wfWikiID();
		if( $this->oConfig->has( 'index_name' ) ) {
			$sIndexName = $this->oConfig->get( 'index_name' );
		}
		return [
			$sIndexName => new IndexManager( $sIndexName, $this->getClient(), $this->getSources() )
		];
	}

	/**
	 *
	 * @var Backend[]
	 */
	protected static $aBackends = [];

	/**
	 *
	 * @param string $sBackendKey
	 * @return Backend
	 */
	public static function instance( $sBackendKey ) {
		if( isset( self::$aBackends[$sBackendKey] ) ) {
			return self::$aBackends[$sBackendKey];
		}

		self::$aBackends[$sBackendKey] = self::newFromConfig(
			self::getConfigFromKey( $sBackendKey )
		);

		return self::$aBackends[$sBackendKey];
	}

	/**
	 *
	 * @param string $aConfig
	 */
	protected static function newFromConfig( $aConfig ) {
		return \ObjectFactory::getObjectFromSpec( $aConfig );
	}

	public static function factoryAll() {
		$oConfig = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsgES' );
		$aBackendConfigs = $oConfig->get( 'Backends' );

		foreach( $aBackendConfigs as $sBackendKey => $aBackendConfig ) {
			self::instance( $sBackendKey );
		}

		return self::$aBackends;
	}

	/**
	 *
	 * @param sting $sBackendKey
	 * @return array
	 * @throws Exception
	 */
	protected static function getConfigFromKey( $sBackendKey ) {
		$oConfig = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsgES' );
		$aBackendConfigs = $oConfig->get( 'Backends' );

		if( !isset( $aBackendConfigs[$sBackendKey] ) ) {
			throw new Exception( "BACKEND: Key '$sBackendKey' not set in config!" );
		}

		return $aBackendConfigs[$sBackendKey];
	}
}