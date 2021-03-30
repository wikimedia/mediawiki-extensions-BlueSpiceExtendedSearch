<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BS\ExtendedSearch\Source\DocumentProvider\File as FileBase;
use File;

class RepoFile extends FileBase {

	/**
	 * @var File
	 */
	protected $file = null;

	/**
	 *
	 * @param string $sUri
	 * @param array $mDataItem
	 * @return array
	 */
	public function getDataConfig( $sUri, $mDataItem ) {
		$this->file = $mDataItem['fsFile'];

		$dc = parent::getDataConfig( $sUri, $this->file );
		$dc = array_merge( $dc, [
			'filename' => $mDataItem['title']
		] );

		return $dc;
	}
}
