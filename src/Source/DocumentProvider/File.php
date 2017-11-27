<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class File extends DecoratorBase {

	/**
	 *
	 * @param string $sUri
	 * @param \SplFileInfo $oFile
	 * @return array
	 */
	public function getDataConfig( $sUri, $oFile ) {
		$aDC = $this->oDecoratedDP->getDataConfig( $sUri, $oFile );
		$magic = \MediaWiki\MediaWikiServices::getInstance()->getMimeAnalyzer();
		$aDC += [
			'basename' => $oFile->getBasename(),
			'extension' => $oFile->getExtension(),
			'mime_type' => $magic->guessMimeType( $oFile->getPathname() ),
			'mtime' => $oFile->getMTime(),
			'ctime' => $oFile->getCTime(),
			'size' => $oFile->getSize(),
			'source_file_path' => $oFile->getPathname(),
			'the_file' => base64_encode(
				file_get_contents(
					$oFile->getPathname()
				)
			)
		];
		return $aDC;
	}
}