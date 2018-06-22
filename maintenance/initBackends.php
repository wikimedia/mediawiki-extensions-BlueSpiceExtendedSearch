<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class initBackends extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->requireExtension( "BlueSpiceExtendedSearch" );

		$this->addOption( 'quick', 'Skip count down' );
	}

	public function execute() {
		if( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete and recreate all registered indices! Starting in ... ' );
			$this->countDown( 5 );
		}

		$backend = BS\ExtendedSearch\Backend::instance();
		$backend->deleteIndexes();
		$backend->createIndexes();
		$this->output( "\nIndexes created" );
	}
}

$maintClass = 'initBackends';
require_once( RUN_MAINTENANCE_IF_MAIN );
