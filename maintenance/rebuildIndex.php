<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class rebuildIndex extends Maintenance {

	protected $oIndices = array();

	public function __construct() {
		parent::__construct();
		$this->requireExtension( "BlueSpiceExtendedSearch" );
		$this->addOption( 'quick', 'Skip count down' );
		$this->addOption( 'sources', "Only these sources will be re-indexed. Need to be specified in form of '<index>/<source>'", false, true );
	}

	public function execute() {
		if( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will create update jobs for all indices! Starting in ... ' );
			wfCountDown( 5 );
		}
		foreach( \BS\ExtendedSearch\Backend::factoryAll() as $sBackendKey => $oBackend ) {
			$aSources = $oBackend->getSources();
			foreach( $aSources as $oSource ) {
				$sSourceKey = $oSource->getTypeKey();
				if( !$this->sourceOnList( "$sBackendKey/$sSourceKey" ) ) {
					continue;
				}

				$this->output( "\nCrawling '$sSourceKey'" );
				$oCrawler = $oSource->getCrawler();
				$oCrawler->clearPendingJobs();
				$oCrawler->crawl();
				$this->output( " done: ". $oCrawler->getNumberOfPendingJobs() );
			}
		}

		global $IP;
		$this->output( "\n\nYou should now run 'php $IP/maintenance/runJobs.php'" );
	}

	protected function sourceOnList( $sSource ) {
		if( empty( $this->getOption( 'sources', '' ) ) ) {
			return true;
		}
		$aOnlySources = explode( '|', $this->getOption( 'sources', '' ) );
		if( in_array( $sSource, $aOnlySources ) ) {
			return true;
		}
		return false;
	}

}

$maintClass = 'rebuildIndex';
require_once RUN_MAINTENANCE_IF_MAIN;
