<?php

namespace BS\ExtendedSearch\Source\Job;

use File;
use Hooks;

class UpdateRepoFile extends UpdateTitleBase {
	/** @var string  */
	protected $sSourceKey = 'repofile';
	/** @var File|null  */
	protected $file = null;
	/** @var string|null  */
	protected $canonicalURL = null;

	/**
	 *
	 * @param \Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		if ( isset( $params['file'] ) ) {
			$this->file = $params['file'];
			$this->canonicalURL = $this->file->getCanonicalUrl();
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
		return $this->canonicalURL;
	}

	/**
	 *
	 * @return File
	 * @throws \Exception
	 */
	protected function getDocumentProviderSource() {
		$this->setFileRepoFile();
		return $this->file;
	}

	/**
	 *
	 * @throws \Exception
	 */
	protected function setFileRepoFile() {
		if ( $this->file instanceof \File ) {
			return;
		}

		$file = \RepoGroup::singleton()->findFile( $this->getTitle() );
		if ( $file === false ) {
			throw new \Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found in any repo!" );
		}
		$this->canonicalURL = $file->getCanonicalURL();
		Hooks::run( 'BSExtendedSearchRepoFileGetRepoFile', [
			&$file
		] );

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
