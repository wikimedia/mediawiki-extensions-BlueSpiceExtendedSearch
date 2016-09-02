<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class initBackends extends Maintenance {

	public function execute() {
		$this->output( 'This will delete and recreate all registered indices! Starting in ... ' );
		wfCountDown( 5 );

		$aBackends = BS\ExtendedSearch\Backend::factoryAll();
		foreach( $aBackends as $sBackendKey => $oBackend ) {
			$aIndexManagers = $oBackend->getIndexManagers();
			foreach( $aIndexManagers as $sIndexName => $oIndexManager ) {
				$oIndexManager->delete();
				$oIndexManager->create();
				$this->output( "\n$sBackendKey: Index '$sIndexName' created" );
			}

		}
	}
}

$maintClass = 'initBackends';
if (defined('RUN_MAINTENANCE_IF_MAIN')) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}