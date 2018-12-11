<?php

namespace BS\ExtendedSearch;

use BlueSpice\ExtensionAttributeBasedRegistry;
use BlueSpice\IRegistry;
use BS\ExtendedSearch\Source\Base;

class SourceFactory {
	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 * @var \Config
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
	 * @var IRegistry
	 */
	protected $sourceRegistry = null;

	/**
	 * SourceFactory constructor.
	 * @param Backend $backend
	 * @param \Config $config
	 */
	public function __construct( $backend, $config ) {
		$this->backend = $backend;
		$this->config = $config;
	}

	public function makeSource( $sourceKey ) {
		if ( isset( $this->sources[$sourceKey] ) ) {
			return $this->sources[$sourceKey];
		}

		$this->assertSourceFactoryFunction( $sourceKey );
		$this->assertSourceConfig( $sourceKey );

		$base = new Base( $this->backend, $this->configs[$sourceKey] );

		$source = call_user_func( $this->factoryFunction[$sourceKey], $base );

		if ( $source instanceof Base ) {
			$this->sources[$sourceKey] = $source;
		} else {
			throw new \UnexpectedValueException( "Factory for $sourceKey returned invalid source object!" );
		}

		return $this->sources[$sourceKey];
	}

	/**
	 * @param string $sourceKey
	 * @throws \InvalidArgumentException
	 */
	protected function assertSourceFactoryFunction( $sourceKey ) {
		if ( isset( $this->factoryFunction[$sourceKey] ) ) {
			return;
		}

		$func = $this->getFactoryFunctionFromAttribute( $sourceKey );
		if ( !is_callable( $func ) ) {
			throw new \InvalidArgumentException( "Invalid callback supplied for source \"$sourceKey\"" );
		}

		$this->factoryFunction[$sourceKey] = $func;
	}

	/**
	 * @param string $sourceKey
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function getFactoryFunctionFromAttribute( $sourceKey ) {
		if ( $this->sourceRegistry === null ) {
			$this->sourceRegistry = new ExtensionAttributeBasedRegistry( 'BlueSpiceExtendedSearchSources' );
		}

		if ( in_array( $sourceKey, $this->sourceRegistry->getAllKeys() ) ) {
			return $this->sourceRegistry->getValue( $sourceKey );
		}

		throw new \InvalidArgumentException( "No registered factory method for source \"$sourceKey\"" );
	}

	/**
	 * @param $sourceKey
	 * @throws \ConfigException
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
