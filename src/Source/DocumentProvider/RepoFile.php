<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BS\ExtendedSearch\Source\DocumentProvider\File as FileBase;
use Exception;
use File;
use SplFileInfo;

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
		$dc = array_merge( $dc, [
			'filename' => $filename
		] );

		return $dc;
	}
}
