<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BS\ExtendedSearch\Source\DocumentProvider\File as FileBase;
use File;
use Title;

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
		$filename = $mDataItem['title'];
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
