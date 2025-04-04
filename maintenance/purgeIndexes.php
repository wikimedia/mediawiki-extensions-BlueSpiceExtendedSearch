<?php

use MediaWiki\MediaWikiServices;

require_once "searchScriptBase.php";

class purgeIndexes extends searchScriptBase { // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separate source keys to be purged';

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete all indexes related to this wiki instance! Starting in ... ' );
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
			$this->output( "\nIndex deleted: {$backend->getIndexName( $sourceKey)}" );
		}
	}
}

$maintClass = purgeIndexes::class;
require_once RUN_MAINTENANCE_IF_MAIN;
