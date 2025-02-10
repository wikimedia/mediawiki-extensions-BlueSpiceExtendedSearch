<?php

namespace BS\ExtendedSearch;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\MultiConfig;
use MediaWiki\Registration\ExtensionRegistry;
use UnexpectedValueException;
use Wikimedia\ObjectFactory\ObjectFactory;

class SourceFactory {

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var array
	 */
	protected $factoryFunction = [];

	/**
	 * Configuration for sources
	 * @var array
	 */
	protected $configs = [];

	/**
	 *
	 * @var array
	 */
	protected $sources = [];

	/**
	 * @var array
	 */
	protected $sourceRegistry = null;

	/**
	 * @var ObjectFactory
	 */
	protected $objectFactory = null;

	/**
	 * @param Config $config
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct( Config $config, ObjectFactory $objectFactory ) {
		$this->config = $config;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @return array
	 */
	public function getSourceKeys(): array {
		if ( $this->sourceRegistry === null ) {
			$this->sourceRegistry = ExtensionRegistry::getInstance()->getAttribute( 'BlueSpiceExtendedSearchSources' );
		}
		return array_keys( $this->sourceRegistry );
	}

	/**
	 *
	 * @param string $sourceKey
	 * @param Backend $backend
	 * @return ISearchSource
	 * @throws UnexpectedValueException
	 */
	public function makeSource( $sourceKey, $backend ) {
		if ( isset( $this->sources[$sourceKey] ) ) {
			return $this->sources[$sourceKey];
		}

		$spec = $this->getSpecFromAttibute( $sourceKey );
		$this->assertSourceConfig( $sourceKey );

		$source = $this->objectFactory->createObject( $spec );
		if ( !( $source instanceof ISearchSource ) ) {
			throw new UnexpectedValueException( "Factory for $sourceKey returned invalid source object!" );
		}
		$source->setBackend( $backend );
		$source->setSourceConfig( new MultiConfig( [
			$this->config,
			new HashConfig( $this->configs[$sourceKey] )
		] ) );
		$this->sources[$sourceKey] = $source;
		return $this->sources[$sourceKey];
	}

	/**
	 *
	 * @param string $sourceKey
	 */
	public function destroySource( $sourceKey ) {
		if ( isset( $this->sources[$sourceKey] ) ) {
			unset( $this->sources[$sourceKey] );
		}
	}

	/**
	 * @param string $sourceKey
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function getSpecFromAttibute( $sourceKey ) {
		if ( $this->sourceRegistry === null ) {
			$this->sourceRegistry = ExtensionRegistry::getInstance()->getAttribute( 'BlueSpiceExtendedSearchSources' );
		}

		if ( isset( $this->sourceRegistry[$sourceKey] ) ) {
			$spec = $this->sourceRegistry[$sourceKey];
			if ( isset( $spec['class'] ) && is_array( $spec['class'] ) ) {
				$spec['class'] = end( $spec['class'] );
			}
			if ( isset( $spec['factory'] ) && is_array( $spec['factory'] ) ) {
				$spec['factory'] = end( $spec['factory'] );
			}
			return $spec;
		}

		throw new \InvalidArgumentException( "No object specification registered for \"$sourceKey\"" );
	}

	/**
	 * @param string $sourceKey
	 * @throws ConfigException
	 */
	protected function assertSourceConfig( $sourceKey ) {
		if ( isset( $this->configs[$sourceKey] ) ) {
			return;
		}

		$sourceConfigs = $this->config->get( 'ESSourceConfig' );

		$config = [];
		if ( isset( $sourceConfigs[$sourceKey] ) ) {
			$config = $sourceConfigs[$sourceKey];
			if ( !is_array( $config ) ) {
				$config = [ $config ];
			}
		}

		$config['sourcekey'] = $sourceKey;

		$this->configs[$sourceKey] = $config;
	}
}
