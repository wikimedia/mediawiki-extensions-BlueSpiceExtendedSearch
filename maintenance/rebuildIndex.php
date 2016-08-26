<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class rebuildIndex extends Maintenance {

	protected $oIndices = array();

	public function __construct() {
		parent::__construct();
		$this->addOption( 'sources', "Only these sources will be re-indexed. Need to be specified in form of '<index>/<source>'", false, true );
	}

	public function execute() {
		#wfCountDown( 5 );

		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'bsgES' );
		$aIndizes = $config->get("Indizes");

		$aSources = [];
		foreach( \BS\ExtendedSearch\Indices::factoryAll() as $oIndex ) {
			$oIndex->create();

			$aSources = $oIndex->getSources();
			foreach( $aSources as $oSource ) {
				$oSource->getCrawler()->crawl();
			}
		}
	}
}

$maintClass = 'rebuildIndex';
if (defined('RUN_MAINTENANCE_IF_MAIN')) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}