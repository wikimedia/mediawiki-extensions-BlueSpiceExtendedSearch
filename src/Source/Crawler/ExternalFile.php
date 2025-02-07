<?php

namespace BS\ExtendedSearch\Source\Crawler;

use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Title\TitleFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;
use Wikimedia\Rdbms\ILoadBalancer;

class ExternalFile extends File {

	/**
	 * @var Config
	 */
	protected $bsgConfig = null;

	/**
	 * @var TitleFactory
	 */
	protected $titleFactory = null;

	/**
	 * @param ILoadBalancer $lb
	 * @param JobQueueGroup $jobQueueGroup
	 * @param TitleFactory $titleFactory
	 * @param ConfigFactory $configFactory
	 * @param Config $sourceConfig
	 */
	public function __construct(
		ILoadBalancer $lb, JobQueueGroup $jobQueueGroup, TitleFactory $titleFactory,
		ConfigFactory $configFactory, Config $sourceConfig
	) {
		parent::__construct( $lb, $jobQueueGroup, $sourceConfig );
		$this->bsgConfig = $configFactory->makeConfig( 'bsg' );
		$this->titleFactory = $titleFactory;
	}

	/** @var string */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateExternalFile';

	/**
	 * @return void
	 */
	public function crawl() {
		parent::crawl();
		$dummyTitle = $this->titleFactory->makeTitle( NS_SPECIAL, 'Dummy title for external file' );

		$paths = $this->bsgConfig->get( 'ESExternalFilePaths' );
		$excludePatterns = (array)$this->bsgConfig->get(
			'ExtendedSearchExternalFilePathsExcludes'
		);

		foreach ( $paths as $sourcePath => $uriPrefix ) {
			$sourceFileInfo = new SplFileInfo( $sourcePath );

			try {
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $sourceFileInfo->getPathname(),
						RecursiveDirectoryIterator::SKIP_DOTS
					),
					RecursiveIteratorIterator::SELF_FIRST
				);
			} catch ( UnexpectedValueException $ex ) {
				wfDebugLog(
					'BSExtendedSearch',
					'Crawling external file failed: ' . $ex->getMessage()
				);
				continue;
			}

			foreach ( $files as $file ) {
				if ( $file->isDir() ) {
					continue;
				}
				$pathExcludePatterns = empty( $excludePatterns[$sourcePath] )
					? ''
					: $excludePatterns[$sourcePath];

				if ( $this->shouldSkip( $file, $pathExcludePatterns ) ) {
					continue;
				}

				$this->addToJobQueue( $dummyTitle, [
					'source' => $this->sourceConfig->get( 'sourcekey' ),
					'src' => $file->getPathname(),
					'dest' => $this->makeDestFileName( $uriPrefix, $file, $sourceFileInfo )
				] );
			}
		}
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @param string $excludePatterns
	 * @return bool
	 */
	protected function shouldSkip( $file, $excludePatterns = '' ) {
		if ( parent::shouldSkip( $file ) ) {
			return true;
		}
		if ( empty( $excludePatterns ) ) {
			return false;
		}
		if ( preg_match( $excludePatterns, $file->getRealPath() ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param string $sUriPrefix
	 * @param SplFileInfo $oFile
	 * @param SplFileInfo $oSourcePath
	 * @return string
	 */
	protected function makeDestFileName( $sUriPrefix, $oFile, $oSourcePath ) {
		$sRelativePath = str_replace( $oSourcePath->getPathname(), '', $oFile->getPathname() );
		$sRelativePath = ltrim( $sRelativePath, '/\\' );
		$sUriPrefix = rtrim( $sUriPrefix, '/\\' );
		return "$sUriPrefix/$sRelativePath";
	}
}
