<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class purgeIndexes extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->requireExtension( "BlueSpiceExtendedSearch" );

		$this->addOption( 'quick', 'Skip count down' );
	}

	public function execute() {
		if( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete all indexes related to this wiki instance! Starting in ... ' );
			wfCountDown( 5 );
		}

		$aBackends = BS\ExtendedSearch\Backend::factoryAll();
		foreach( $aBackends as $sBackendKey => $oBackend ) {
			$oBackend->deleteAllIndexes();
			$this->output( "\n$sBackendKey: Indexes purged" );
		}
	}
}

$maintClass = 'purgeIndexes';
require_once( RUN_MAINTENANCE_IF_MAIN );