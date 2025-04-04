<?php

use MediaWiki\MediaWikiServices;

require_once "searchScriptBase.php";

class initBackends extends searchScriptBase { // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separated source keys to be initiated';

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete and recreate all registered indices! Starting in ... ' );
			$this->countDown( 5 );
		}

		$backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
		$sources = $backend->getSources();
		foreach ( $sources as $source ) {
			$sourceKey = $source->getTypeKey();
			if ( !$this->sourceOnList( $sourceKey ) ) {
				continue;
			}
			$backend->deleteIndex( $sourceKey );
			$backend->createIndex( $sourceKey );
			$this->output( "\nIndex created: {$backend->getIndexName( $sourceKey)}" );
		}
	}
}

$maintClass = initBackends::class;
require_once RUN_MAINTENANCE_IF_MAIN;
