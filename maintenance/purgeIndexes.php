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
			$this->countDown( 5 );
		}

		$backend = \BS\ExtendedSearch\Backend::instance();
		$backend->deleteAllIndexes();
		$this->output( "\nIndexes purged" );
	}
}

$maintClass = 'purgeIndexes';
require_once( RUN_MAINTENANCE_IF_MAIN );
