<?php

require_once "elasticScriptBase.php";

class rebuildIndex extends elasticScriptBase {
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separate source keys to be rebuilt';
	/**
	 * @var array
	 */
	protected $oIndices = [];

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will create update jobs for all indices! Starting in ... ' );
			$this->countDown( 5 );
		}

		$backend = \BS\ExtendedSearch\Backend::instance();
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

		global $IP;
		$this->output( "\n\nYou should now run 'php $IP/maintenance/runJobs.php'" );
	}
}

$maintClass = 'rebuildIndex';
require_once RUN_MAINTENANCE_IF_MAIN;
