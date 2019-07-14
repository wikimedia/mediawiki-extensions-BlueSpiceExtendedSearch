<?php

require_once "elasticScriptBase.php";

class purgeIndexes extends elasticScriptBase {
	/**
	 * @var string
	 */
	protected $sourcesOptionHelp = 'List of pipe separate source keys to be purged';

	public function execute() {
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( 'This will delete all indexes related to this wiki instance! Starting in ... ' );
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
			$this->output( "\nIndex deleted: {$backend->getIndexByType( $sourceKey )->getName()}" );
		}
	}
}

$maintClass = 'purgeIndexes';
require_once RUN_MAINTENANCE_IF_MAIN;
