<?php

namespace BS\ExtendedSearch;

use BlueSpice\ExtensionAttributeBasedRegistry;
use Config;
use IContextSource;
use MediaWiki\MediaWikiServices;

class LookupModifierFactory {
	/**
	 *
	 * @var Config
	 */
	protected $config = null;

	/**
	 *
	 * @var ExtensionAttributeBasedRegistry
	 */
	protected $registry = null;

	/**
	 *
	 * @var ExtensionAttributeBasedRegistry
	 */
	protected $legacyRegistry = null;

	/**
	 *
	 * @param Config $config
	 * @param ExtensionAttributeBasedRegistry $registry
	 * @param ExtensionAttributeBasedRegistry $legacyRegistry
	 */
	public function __construct( Config $config, ExtensionAttributeBasedRegistry $registry,
		ExtensionAttributeBasedRegistry $legacyRegistry ) {
		$this->config = $config;
		$this->registry = $registry;
		$this->legacyRegistry = $legacyRegistry;
	}

	/**
	 *
	 * @return array
	 */
	public function getTypes() {
		return $this->registry->getAllKeys();
	}

	/**
	 *
	 * @param string $name
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return ILookupModifier|null
	 */
	public function newFromName( $name, Lookup $lookup, IContextSource $context ) {
		$callback = $this->registry->getValue( $name, false );
		if ( !$callback ) {
			$legacyCallbacks = $this->legacyRegistry->getAllValues();
			if ( !empty( $legacyCallbacks ) ) {
				return null;
			}
			foreach ( $legacyCallbacks as $sourceType => $callbacks ) {
				// ignore $sourceType for now and let the lookups do their thing
				if ( !isset( $callbacks[$name] ) ) {
					continue;
				}
				// deprecated since version 3.1.13
				wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
				$callback = $callbacks[$name];
				break;
			}
		}
		if ( !is_callable( $callback ) ) {
			if ( !class_exists( $callback ) ) {
				return null;
			}
			// deprecated since version 3.1.13
			wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
			return new $callback( $lookup, $context );
		}
		return call_user_func_array( $callback, [
			MediaWikiServices::getInstance(),
			$lookup,
			$context
		] );
	}

	/**
	 *
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return ILookupModifier[]
	 */
	public function getLookupModifiers( Lookup $lookup, IContextSource $context ) {
		$keys = array_merge( $this->getLegacyKeys(), $this->registry->getAllKeys() );
		$lookupModifiers = [];
		foreach ( $keys as $key ) {
			$instance = $this->newFromName( $key, $lookup, $context );
			if ( !$instance ) {
				continue;
			}
			$lookupModifiers[$key] = $instance;
		}
		return $lookupModifiers;
	}

	/**
	 *
	 * @param string $type
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return ILookupModifier[]
	 */
	public function getLookupModifiersForQueryType( $type, Lookup $lookup,
		IContextSource $context ) {
		return array_filter(
			$this->getLookupModifiers( $lookup, $context ),
			static function ( ILookupModifier $lM ) use( $type ) {
				return in_array( $type, $lM->getSearchTypes() );
			} );
	}

	/**
	 *
	 * @return array
	 */
	private function getLegacyKeys() {
		$legacyKeys = [];
		foreach ( $this->legacyRegistry->getAllValues() as $sourceType => $callbacks ) {
			$legacyKeys = array_merge( $legacyKeys, array_keys( $callbacks ) );
		}
		return $legacyKeys;
	}

}
