<?php

require_once "elasticScriptBase.php";

class initBackends extends elasticScriptBase {
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separated source keys to be initiated';

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete and recreate all registered indices! Starting in ... ' );
			$this->countDown( 5 );
		}

		$backend = BS\ExtendedSearch\Backend::instance();
		$sources = $backend->getSources();
		foreach ( $sources as $source ) {
			$sourceKey = $source->getTypeKey();
			if ( !$this->sourceOnList( $sourceKey ) ) {
				continue;
			}
			$backend->deleteIndex( $sourceKey );
			$backend->createIndex( $sourceKey );
			$this->output( "\nIndex created: {$backend->getIndexByType( $sourceKey)->getName()}" );
		}
	}
}

$maintClass = 'initBackends';
require_once RUN_MAINTENANCE_IF_MAIN;
