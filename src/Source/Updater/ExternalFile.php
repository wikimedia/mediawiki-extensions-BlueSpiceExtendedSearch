<?php

namespace BS\ExtendedSearch\Source\Updater;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Status\Status;
use MWStake\MediaWiki\Component\RunJobsTrigger\IHandler;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval\OnceADay;
use SplFileInfo;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LoadBalancer;

class ExternalFile implements IHandler {
	/** @var string */
	protected $sourceKey = 'externalfile';
	/** @var Config */
	private $config;
	/** @var LoadBalancer */
	protected $lb;
	/** @var Backend */
	protected $backend;
	/** @var string */
	protected $index;
	/** @var array */
	protected $indexedFiles = [];

	/**
	 * @param ConfigFactory $configFactory
	 * @param ILoadBalancer $loadBalancer
	 * @param Backend $searchBackend
	 */
	public function __construct( ConfigFactory $configFactory, ILoadBalancer $loadBalancer, Backend $searchBackend ) {
		$this->config = $configFactory->makeConfig( 'bsg' );
		$this->lb = $loadBalancer;
		$this->backend = $searchBackend;
		$this->index = $this->backend->getIndexName( $this->sourceKey );
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function removeDeletedFilesFromIndex() {
		$this->getIndexedExternalFiles();
		$this->filterOutExistingFilesInPaths();
		$this->bulkDeleteFiles();
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function getIndexedExternalFiles() {
		$lookup = new Lookup();
		$lookup->addSourceField( 'source_file_path' );
		$lookup->setQueryString( '*' );
		$lookup->setSize( 25 );

		$files = [];
		$results = [];
		$this->getResults( $lookup, $results );
		foreach ( $results as $result ) {
			$files[$result->getId()] = $result->getSourceParam( 'source_file_path' );
		}
		$this->indexedFiles = $files;
	}

	/**
	 *
	 * @param Lookup $lookup
	 * @param array &$results
	 *
	 * @throws \Exception
	 */
	protected function getResults( $lookup, &$results ) {
		$res = $this->backend->runRawQuery( $lookup, [ 'externalfile' ] );
		if ( count( $res->getResults() ) === 0 ) {
			return;
		}
		$results = array_merge( $results, $res->getResults() );
		$size = $lookup->getSize();
		$from = $lookup->getFrom();
		$from = $from + $size;
		$lookup->setFrom( $from );
		$this->getResults( $lookup, $results );
	}

	/**
	 * @return void
	 */
	protected function filterOutExistingFilesInPaths() {
		foreach ( $this->indexedFiles as $id => $path ) {
			if ( file_exists( $path ) && $this->inPaths( $path ) ) {
				unset( $this->indexedFiles[ $id ] );
			}
		}
	}

	/**
	 * Checks if indexed file is in paths configured
	 * to be indexed
	 *
	 * @param string $path
	 * @return bool
	 */
	protected function inPaths( $path ) {
		$paths = $this->config->get( 'ESExternalFilePaths' );
		$excludePatterns = (array)$this->config->get(
			'ExtendedSearchExternalFilePathsExcludes'
		);

		foreach ( $paths as $configuredPath ) {
			$filePathInfo = new SplFileInfo( $configuredPath );
			$file = new SplFileInfo( $path );
			$pathExcludePatterns = empty( $excludePatterns[$configuredPath] )
				? ''
				: $excludePatterns[$configuredPath];

			if ( strpos( $file->getPathname(), $filePathInfo->getPathname() ) !== 0 ) {
				continue;
			}
			if ( empty( $pathExcludePatterns ) ) {
				return true;
			}
			if ( preg_match( $pathExcludePatterns, $file->getRealPath() ) < 1 ) {
				return true;
			}
			return false;
		}

		return false;
	}

	/**
	 * Removes all files that should no longer be in index
	 */
	protected function bulkDeleteFiles() {
		$docs = [];
		foreach ( $this->indexedFiles as $id => $path ) {
			$docs[] = [
				'delete' => [
					'_id' => $id,
					'_index' => $this->index,
				]
			];
		}

		if ( empty( $docs ) ) {
			return;
		}

		$this->backend->getClient()->bulk( [
			'index' => $this->index,
			'body' => $docs,
		] );
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return 'bs-extendedsearch-update-external-files';
	}

	/**
	 * @return Status
	 * @throws \Exception
	 */
	public function run() {
		// 1. - Run crawler to handle new files/paths and updates
		$crawler = $this->backend->getSource( $this->sourceKey )->getCrawler();
		$crawler->crawl();

		// 2. - Check if all indexed files exist
		$this->removeDeletedFilesFromIndex();

		return Status::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function getInterval() {
		return new OnceADay();
	}
}
