<?php

namespace BS\ExtendedSearch\Source\Updater;

use BlueSpice\RunJobsTriggerHandler;

class ExternalFile extends RunJobsTriggerHandler {
	protected $sourceKey = 'externalfile';
	protected $backend;
	protected $index;

	protected $indexedFiles = [];

	public function __construct( $config, $loadBalancer, $notifier ) {
		parent::__construct( $config, $loadBalancer, $notifier );

		$this->backend = \BS\ExtendedSearch\Backend::instance();
		$this->index = $this->backend->getIndexByType( $this->sourceKey );
	}
	protected function doRun() {
		// 1. - Run crawler to handle new files/paths and updates
		$crawler = new \BS\ExtendedSearch\Source\Crawler\ExternalFile(
			new \HashConfig( [ 'sourcekey' => $this->sourceKey ] )
		);
		$crawler->crawl();

		// 2. - Check if all indexed files exist
		$this->removeDeletedFilesFromIndex();

		return \Status::newGood();
	}

	protected function removeDeletedFilesFromIndex() {
		$this->getIndexedExternalFiles();
		$this->filterOutExistingFilesInPaths();
		$this->bulkDeleteFiles();
	}

	protected function getIndexedExternalFiles() {
		$search = new \Elastica\Search( $this->backend->getClient() );
		$search->addIndex( $this->index->getName() );

		$lookup = new \BS\ExtendedSearch\Lookup();
		$lookup->addSourceField( 'source_file_path' );
		$lookup->setQueryString( '*' );
		$lookup->setSize( 25 );
		$count = $search->count( $lookup->getQueryDSL() );

		$files = [];
		$results = [];
		$this->getResults( $search, $lookup, $results );
		foreach( $results as $result ) {
			$files[ $result->getId() ] = $result->__get( 'source_file_path' );
		}

		$this->indexedFiles = $files;
	}

	protected function getResults( $search, $lookup, &$results ) {
		$res = $search->search( $lookup->getQueryDSL() );
		if( count( $res->getResults() ) == 0 ) {
			return;
		}
		$results = array_merge( $results, $res->getResults() );
		$size = $lookup->getSize();
		$from = $lookup->getFrom();
		$from = (int) $from + (int) $size;
		$lookup->setFrom( $from );
		$this->getResults( $search, $lookup, $results );
	}

	protected function filterOutExistingFilesInPaths() {
		foreach( $this->indexedFiles as $id => $path ) {
			if( file_exists( $path ) && $this->inPaths( $path ) ) {
				unset( $this->indexedFiles[ $id ] );
			}
		}
	}

	/**
	 * Checks if indexed file is in paths configured
	 * to be indexed
	 *
	 * @param string $path
	 * @return boolean
	 */
	protected function inPaths( $path ) {
		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsg' );
		$paths = $config->get( 'ESExternalFilePaths' );

		foreach( $paths as $configuredPath ) {
			$filePathInfo = new \SplFileInfo( $configuredPath );
			$file = new \SplFileInfo( $path );
			if( strpos( $file->getPathname(), $filePathInfo->getPathname() ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Removes all files that should no longer be in index
	 */
	protected function bulkDeleteFiles() {
		$docs = [];
		foreach( $this->indexedFiles as $id => $path ) {
			$docs[] = new \Elastica\Document( $id );
		}

		$bulk = new \Elastica\Bulk( $this->backend->getClient() );
		$bulk->setIndex( $this->index->getName() );
		$bulk->setType( $this->sourceKey );
		$bulk->addDocuments( $docs, \Elastica\Bulk\Action::OP_TYPE_DELETE );
		$bulk->send();
	}
}
