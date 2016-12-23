<?php

namespace BS\ExtendedSearch\Source\Crawler;

class ExternalFile extends Base {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateExternalFile';

	public function crawl() {
		$oDummyTitle = \Title::makeTitle( NS_SPECIAL, 'Dummy title for external file' );

		$sPaths = $this->oConfig->get( 'paths' );
		foreach( $sPaths as $sSourcePath => $sUriPrefix ) {
			$oSourcePath = new \SplFileInfo( $sSourcePath );

			$aFiles = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $oSourcePath->getPathname(),
					\RecursiveDirectoryIterator::SKIP_DOTS
				),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach( $aFiles as $oFile ) {
				$oFile instanceof \SplFileInfo;
				if( $oFile->isDir() ) {
					continue;
				}

				$this->addToJobQueue( $oDummyTitle, [
					'source' => $this->oConfig->get( 'sourcekey' ),
					'src' => $oFile->getPathname(),
					'dest' => $this->makeDestFileName( $sUriPrefix, $oFile, $oSourcePath )
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
		$sRelativePath = preg_replace( "#^{$oSourcePath->getPathname()}#", '', $oFile->getPathname() );
		return "$sUriPrefix/$sRelativePath";
	}
}