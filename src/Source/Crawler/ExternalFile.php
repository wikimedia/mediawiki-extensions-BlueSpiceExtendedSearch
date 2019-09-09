<?php

namespace BS\ExtendedSearch\Source\Crawler;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SplFileInfo;

class ExternalFile extends File {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateExternalFile';

	public function crawl() {
		$dummyTitle = \Title::makeTitle( NS_SPECIAL, 'Dummy title for external file' );

		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsg' );
		$paths = $config->get( 'ESExternalFilePaths' );
		$excludePatterns = (array)$config->get(
			'ExtendedSearchExternalFilePathsExcludes'
		);

		foreach ( $paths as $sourcePath => $uriPrefix ) {
			$sourceFileInfo = new SplFileInfo( $sourcePath );

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $sourceFileInfo->getPathname(),
					RecursiveDirectoryIterator::SKIP_DOTS
				),
				RecursiveIteratorIterator::SELF_FIRST
			);

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
					'source' => $this->oConfig->get( 'sourcekey' ),
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
	 */
	protected function makeDestFileName( $sUriPrefix, $oFile, $oSourcePath ) {
		$sRelativePath = str_replace( $oSourcePath->getPathname() . '/', '', $oFile->getPathname() );
		$sRelativePath = ltrim( $sRelativePath, '/\\' );
		$sUriPrefix = rtrim( $sUriPrefix, '/\\' );
		return "$sUriPrefix/$sRelativePath";
	}
}
