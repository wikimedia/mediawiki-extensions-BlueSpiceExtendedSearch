<?php

use BlueSpice\ExtensionAttributeBasedRegistry;
use BS\ExtendedSearch\ExternalIndexFactory;
use BS\ExtendedSearch\Plugin\ISearchPlugin;
use BS\ExtendedSearch\SourceFactory;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'BSExtendedSearchSourceFactory' => static function ( MediaWikiServices $services ) {
		return new SourceFactory(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$services->getObjectFactory()
		);
	},

	'BSExtendedSearchExternalIndexFactory' => static function ( MediaWikiServices $services ) {
		$registry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceExtendedSearchExternalIndexRegistry'
		);
		return new ExternalIndexFactory(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$registry
		);
	},

	'BSExtendedSearchBackend' => static function ( MediaWikiServices $services ) {
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		$pluginInstances = [];
		$plugins = ExtensionRegistry::getInstance()->getAttribute( 'BlueSpiceExtendedSearchPluginRegistry' );
		foreach ( $plugins as $spec ) {
			$instance = $services->getObjectFactory()->createObject( $spec );
			$pluginInstances[] = $instance;
		}
		$services->getHookContainer()->run( 'BSExtendedSearchRegisterPlugin', [ &$pluginInstances ] );
		foreach ( $pluginInstances as $plugin ) {
			if ( !( $plugin instanceof ISearchPlugin ) ) {
				throw new Exception(
					'Search plugin must implement ' . ISearchPlugin::class . ', got ' . get_class( $plugin )
				);
			}
		}

		$backendClass = $config->get( 'ESBackendClass' );
		return new $backendClass(
			$config,
			$services->getDBLoadBalancer(),
			$services->getHookContainer(),
			$services->getService( 'BSExtendedSearchSourceFactory' ),
			$pluginInstances
		);
	},
];

// @codeCoverageIgnoreEnd
