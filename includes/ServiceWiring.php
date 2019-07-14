<?php

return [
	'BSExtendedSearchSourceFactory' => function ( \MediaWiki\MediaWikiServices $services ) {
		return new \BS\ExtendedSearch\SourceFactory(
			\BS\ExtendedSearch\Backend::instance(),
			$services->getConfigFactory()->makeConfig( 'bsg' )
		);
	},

	'BSExtendedSearchExternalIndexFactory' => function ( \MediaWiki\MediaWikiServices $services ) {
		$registry = new \BlueSpice\ExtensionAttributeBasedRegistry(
			'BlueSpiceExtendedSearchExternalIndexRegistry'
		);
		return new \BS\ExtendedSearch\ExternalIndexFactory(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$registry
		);
	}
];
