<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class rebuildIndex extends Maintenance {

	protected $oIndices = array();

	public function __construct() {
		parent::__construct();
		//$this->requireExtension( 'BlueSpiceExtendedSearch' ); //Enable for REL1_28+

		$this->addOption( 'quick', 'Skip count down' );
		$this->addOption( 'sources', "Only these sources will be re-indexed. Need to be specified in form of '<index>/<source>'", false, true );
	}

	public function execute() {
		if( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will create update jobs for all indices! Starting in ... ' );
			wfCountDown( 5 );
		}

		foreach( \BS\ExtendedSearch\Backend::factoryAll() as $oBackend ) {
			$aSources = $oBackend->getSources();
			foreach( $aSources as $oSource ) {
				$this->output( "\nCrawling '{$oSource->getTypeKey()}'" );
				$oCrawler = $oSource->getCrawler();
				$oCrawler->clearPendingJobs();
				$oCrawler->crawl();
				$this->output( " done: ". $oCrawler->getNumberOfPendingJobs() );
			}
		}

		global $IP;
		$this->output( "\n\nYou should now run 'php $IP/maintenance/runJobs.php'" );
	}
}

$maintClass = 'rebuildIndex';
if (defined('RUN_MAINTENANCE_IF_MAIN')) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}