<?php

use MediaWiki\MediaWikiServices;

require_once "searchScriptBase.php";

class PurgeDocuments extends searchScriptBase {
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separate source keys to be purged';

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete all documents related to this wiki instance! Starting in ... ' );
			$this->countDown( 5 );
		}

		$backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
		$sources = $backend->getSources();
		foreach ( $sources as $source ) {
			$sourceKey = $source->getTypeKey();
			if ( !$this->sourceOnList( $sourceKey ) ) {
				continue;
			}
			$backend->purgeDocuments( $sourceKey );
			$this->output( "\nDocuments purged on index: {$backend->getIndexName( $sourceKey)}" );
		}
	}
}

$maintClass = PurgeDocuments::class;
require_once RUN_MAINTENANCE_IF_MAIN;
