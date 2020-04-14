<?php

namespace BS\ExtendedSearch\Source\Job;

use MediaWiki\MediaWikiServices;

class UpdateRepoFile extends UpdateTitleBase {
	protected $sSourceKey = 'repofile';
	protected $file = null;

	/**
	 *
	 * @param \Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		if ( isset( $params['file'] ) ) {
			$this->file = $params['file'];
		}

		if ( isset( $params['action'] ) ) {
			$this->action = $params['action'];
		}

		parent::__construct( 'updateRepoFileIndex', $title, $params );
	}

	/**
	 *
	 * @return string
	 */
	protected function getDocumentProviderUri() {
		$this->setFileRepoFile();
		return $this->file->getCanonicalUrl();
	}

	/**
	 *
	 * @return \SplFileInfo
	 * @throws \Exception
	 */
	protected function getDocumentProviderSource() {
		$this->setFileRepoFile();
		$fileBackend = $this->file->getRepo()->getBackend();
		$fsFile = $fileBackend->getLocalReference( [
			'src' => $this->file->getPath()
		] );

		if ( $fsFile === null ) {
			throw new \Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found on filesystem!" );
		}

		return new \SplFileInfo( $fsFile->getPath() );
	}

	/**
	 *
	 * @throws \Exception
	 */
	protected function setFileRepoFile() {
		if ( $this->file instanceof \File ) {
			return;
		}

		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $this->getTitle() );
		if ( $file === false ) {
			throw new \Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found in any repo!" );
		}
		$this->file = $file;
	}

	/**
	 *
	 * @return bool
	 */
	protected function isDeletion() {
		return false;
	}

	public function __destruct() {
		$this->file = null;
		unset( $this->file );
	}
}
