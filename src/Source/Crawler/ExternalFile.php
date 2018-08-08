<?php

namespace BS\ExtendedSearch\Source\Crawler;

class ExternalFile extends Base {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateExternalFile';

	public function crawl() {
		$dummyTitle = \Title::makeTitle( NS_SPECIAL, 'Dummy title for external file' );

		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsg' );
		$paths = $config->get( 'ESExternalFilePaths' );

		foreach( $paths as $sourcePath => $uriPrefix ) {
			$sourceFileInfo = new \SplFileInfo( $sourcePath );

			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $sourceFileInfo->getPathname(),
					\RecursiveDirectoryIterator::SKIP_DOTS
				),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach( $files as $file ) {
				$file instanceof \SplFileInfo;
				if( $file->isDir() ) {
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
	 * @param string $sUriPrefix
	 * @param \SplFileInfo $oFile
	 * @param \SplFileInfo $oSourcePath
	 */
	protected function makeDestFileName( $sUriPrefix, $oFile, $oSourcePath ) {
		$sRelativePath = str_replace( $oSourcePath->getPathname(), '', $oFile->getPathname() );
		return "$sUriPrefix/$sRelativePath";
	}
}