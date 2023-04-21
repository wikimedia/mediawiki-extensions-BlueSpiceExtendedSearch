<?php

use BlueSpice\ExtensionAttributeBasedRegistry;
use BS\ExtendedSearch\LookupModifierFactory;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'BSExtendedSearchSourceFactory' => function ( MediaWikiServices $services ) {
		return new \BS\ExtendedSearch\SourceFactory(
			// deprecated since version 3.1.13
			null,
			$services->getConfigFactory()->makeConfig( 'bsg' )
		);
	},

	'BSExtendedSearchExternalIndexFactory' => function ( MediaWikiServices $services ) {
		$registry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceExtendedSearchExternalIndexRegistry'
		);
		return new \BS\ExtendedSearch\ExternalIndexFactory(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$registry
		);
	},

	'BSExtendedSearchLookupModifierFactory' => function ( MediaWikiServices $services ) {
		$registry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceExtendedSearchLookupModifierRegistry'
		);
		$legacyRegistry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceExtendedSearchAdditionalLookupModifiers'
		);
		return new LookupModifierFactory(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$registry,
			$legacyRegistry
		);
	},

	'BSExtendedSearchBackend' => function ( MediaWikiServices $services ) {
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		$backendClass = $config->get( 'ESBackendClass' );
		$backendHost = $config->get( 'ESBackendHost' );
		$backendPort = $config->get( 'ESBackendPort' );
		$backendTransport = $config->get( 'ESBackendTransport' );
		$sourceRegistry = new ExtensionAttributeBasedRegistry(
			'BlueSpiceExtendedSearchSources'
		);
		$sources = $sourceRegistry->getAllKeys();

		// deprecated since version 3.1.13
		$legacyConfig = [
			'connection' => [
				'host' => $backendHost,
				'port' => $backendPort,
				'transport' => $backendTransport
			],
			'sources' => $sources
		];

		return new $backendClass(
			$config,
			$services->getDBLoadBalancer(),
			$services->getHookContainer(),
			$services->getService( 'BSExtendedSearchSourceFactory' ),
			$services->getService( 'BSExtendedSearchLookupModifierFactory' ),
			$legacyConfig
		);
	},
];

// @codeCoverageIgnoreEnd
