<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use MimeAnalyzer;

class File extends Base {

	/**
	 * @var MimeAnalyzer
	 */
	protected $mimeAnalyzer = null;

	/**
	 * @param MimeAnalyzer $mimeAnalyzer
	 */
	public function __construct( MimeAnalyzer $mimeAnalyzer ) {
		$this->mimeAnalyzer = $mimeAnalyzer;
	}

	/**
	 * @inheritDoc
	 */
	public function getDocumentData( $sUri, string $documentId, $oFile ): array {
		$contents = file_get_contents(
			$oFile->getPathname()
		);
		$contents = base64_encode( $contents );

		$aDC = parent::getDocumentData( $sUri, $documentId, $oFile );
		$name = $this->removeArchiveName( $oFile->getBasename() );

		// Fallback to the lesser of mtime and ctime due to inconsistent creation time storage
		$cTime = min( $oFile->getMTime(), $oFile->getCTime() );

		return array_merge( $aDC, [
			'basename' => $name,
			'basename_exact' => $name,
			'extension' => $oFile->getExtension(),
			'mime_type' => $this->mimeAnalyzer->guessMimeType( $oFile->getPathname() ),
			'mtime' => $oFile->getMTime(),
			'ctime' => $cTime,
			'size' => $oFile->getSize(),
			'source_file_path' => $oFile->getPathname(),
			'the_file' => $contents
		] );
	}

	/**
	 * Make sure that the archive name for local files is stripped
	 *
	 * @param string $name
	 * @return string
	 */
	private function removeArchiveName( $name ) {
		$bits = explode( '!', $name );
		return array_pop( $bits );
	}
}
