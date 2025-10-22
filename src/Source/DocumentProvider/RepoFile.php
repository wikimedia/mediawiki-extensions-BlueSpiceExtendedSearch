<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BS\ExtendedSearch\Source\DocumentProvider\File as FileBase;
use File;
use MediaWiki\Title\Title;

class RepoFile extends FileBase {

	/**
	 * @var File
	 */
	protected $file = null;

	/**
	 *
	 * @param string $sUri
	 * @param string $documentId
	 * @param array $mDataItem
	 *
	 * @return array
	 */
	public function getDocumentData( $sUri, string $documentId, $mDataItem ): array {
		$this->file = $mDataItem['fsFile'];

		$dc = parent::getDocumentData( $sUri, $documentId, $this->file );
		$filename = $mDataItem['title'];
		$fileTitle = Title::newFromText( $filename );

		return array_merge( $dc, [
			'filename' => $filename,
			'namespace' => $fileTitle ? $fileTitle->getNamespace() : 0,
			'namespace_text' => $this->getNamespaceText( $fileTitle ),
		] );
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
			return wfMessage( 'bs-ns_main' )->text();
		}
		return $title->getNsText();
	}
}
