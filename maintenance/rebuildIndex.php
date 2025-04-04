<?php

use MediaWiki\MediaWikiServices;

require_once "searchScriptBase.php";

class rebuildIndex extends searchScriptBase { // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separate source keys to be rebuilt';

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will create update jobs for all indices! Starting in ... ' );
			$this->countDown( 5 );
		}

		$backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
		$sources = $backend->getSources();
		foreach ( $sources as $source ) {
			$sourceKey = $source->getTypeKey();
			if ( !$this->sourceOnList( $sourceKey ) ) {
				continue;
			}

			$this->output( "\nCrawling '$sourceKey'" );
			$crawler = $source->getCrawler();
			$crawler->clearPendingJobs();
			$crawler->crawl();
			$this->output( " done: " . $crawler->getNumberOfPendingJobs() );
		}

		$IP = $GLOBALS['IP'];
		$this->output( "\n\nYou should now run 'php $IP/maintenance/runJobs.php'" );
	}
}

$maintClass = rebuildIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
