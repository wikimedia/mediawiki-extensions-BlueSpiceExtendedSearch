<?php

return [
	'BSExtendedSearchSourceFactory' => function ( \MediaWiki\MediaWikiServices $services ) {

		return new \BS\ExtendedSearch\SourceFactory(
			\BS\ExtendedSearch\Backend::instance(),
			$services->getConfigFactory()->makeConfig( 'bsg' )
		);
	}
];
