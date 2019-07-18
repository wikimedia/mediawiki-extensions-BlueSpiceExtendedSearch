<?php

$IP = dirname( dirname( dirname( __DIR__ ) ) );

require_once "$IP/maintenance/Maintenance.php";

abstract class elasticScriptBase extends Maintenance {
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = '';

	public function __construct() {
		parent::__construct();
		$this->requireExtension( "BlueSpiceExtendedSearch" );

		$this->addOption( 'quick', 'Skip count down' );
		$this->addOption( 'sources', $this->sourcesOptionHelp, false, true );
	}

	protected function sourceOnList( $sourceKey ) {
		if ( empty( $this->getOption( 'sources', '' ) ) ) {
			return true;
		}
		$onlySources = explode( '|', $this->getOption( 'sources', '' ) );
		if ( in_array( $sourceKey, $onlySources ) ) {
			return true;
		}
		return false;
	}

}
