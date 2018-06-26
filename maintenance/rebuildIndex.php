<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class rebuildIndex extends Maintenance {

	protected $oIndices = array();

	public function __construct() {
		parent::__construct();
		$this->requireExtension( "BlueSpiceExtendedSearch" );
		$this->addOption( 'quick', 'Skip count down' );
		$this->addOption( 'sources', "Only these sources will be re-indexed.", false, true );
	}

	public function execute() {
		if( !$this->hasOption( 'quick' ) ) {
			$this->output('This will create update jobs for all indices! Starting in ... ');
			$this->countDown(5 );
		}

		$backend = \BS\ExtendedSearch\Backend::instance();
		$sources = $backend->getSources();
		foreach( $sources as $source ) {
			$sourceKey = $source->getTypeKey();
			if( !$this->sourceOnList( $sourceKey ) ) {
				continue;
			}

			$this->output( "\nCrawling '$sourceKey'" );
			$crawler = $source->getCrawler();
			$crawler->clearPendingJobs();
			$crawler->crawl();
			$this->output( " done: ". $crawler->getNumberOfPendingJobs() );
		}

		global $IP;
		$this->output( "\n\nYou should now run 'php $IP/maintenance/runJobs.php'" );
	}

	protected function sourceOnList( $sourceKey ) {
		if( empty( $this->getOption( 'sources', '' ) ) ) {
			return true;
		}
		$onlySources = explode( '|', $this->getOption( 'sources', '' ) );
		if( in_array( $sourceKey, $onlySources ) ) {
			return true;
		}
		return false;
	}

}

$maintClass = 'rebuildIndex';
require_once RUN_MAINTENANCE_IF_MAIN;
