<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class initBackends extends Maintenance {
	public function __construct() {
		parent::__construct();
		//$this->requireExtension( 'BlueSpiceExtendedSearch' ); //Enable for REL1_28+

		$this->addOption( 'quick', 'Skip count down' );
	}

	public function execute() {
		if( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete and recreate all registered indices! Starting in ... ' );
			wfCountDown( 5 );
		}

		$aBackends = BS\ExtendedSearch\Backend::factoryAll();
		foreach( $aBackends as $sBackendKey => $oBackend ) {
			$oBackend->deleteIndex();
			$oBackend->createIndex();
			$this->output( "\n$sBackendKey: Index created" );
		}
	}
}

$maintClass = 'initBackends';
require_once( RUN_MAINTENANCE_IF_MAIN );