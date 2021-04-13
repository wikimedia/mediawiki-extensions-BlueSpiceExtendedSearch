<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BS\ExtendedSearch\Source\DocumentProvider\File as FileBase;
use Exception;
use File;
use SplFileInfo;
use Title;

class RepoFile extends FileBase {

	/**
	 * @var File
	 */
	protected $file = null;

	/**
	 *
	 * @param string $sUri
	 * @param File $mDataItem
	 * @return array
	 */
	public function getDataConfig( $sUri, $mDataItem ) {
		$this->file = $mDataItem;
		$fileBackend = $this->file->getRepo()->getBackend();
		$fsFile = $fileBackend->getLocalReference( [
			'src' => $this->file->getPath()
		] );
		$filename = $this->file->getTitle()->getDBkey();
		if ( $fsFile === null ) {
			throw new Exception( "File '$filename' not found on filesystem!" );
		}

		$localFile = new SplFileInfo( $fsFile->getPath() );
		$dc = parent::getDataConfig( $sUri, $localFile );

		$fileTitle = Title::newFromText( $filename );
		$dc = array_merge( $dc, [
			'filename' => $filename,
			'namespace' => $fileTitle ? $fileTitle->getNamespace() : 0,
			'namespace_text' => $this->getNamespaceText( $fileTitle ),
		] );

		return $dc;
	}

	/**
	 *
	 * @param Title|null $title
	 * @return string
	 */
	protected function getNamespaceText( $title ) {
		if ( !$title instanceof Title ) {
			return '';
		}
		if ( $title->getNamespace() === NS_MAIN ) {
			return wfMessage( 'bs-ns_main' )->plain();
		}
		return $title->getNsText();
	}
}
