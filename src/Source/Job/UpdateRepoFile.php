<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateRepoFile extends UpdateTitleBase {
	protected $sSourceKey = 'repofile';
	protected $file = null;

	/**
	 *
	 * @param \Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		if( isset( $params['file'] ) ) {
			$this->file = $params['file'];
		}

		if( isset( $params['action'] ) ) {
			$this->action = $params['action'];
		}

		parent::__construct( 'updateRepoFileIndex', $title, $params );
	}

	protected function getDocumentProviderUri() {
		$this->setFileRepoFile();
		return $this->file->getCanonicalUrl();
	}

	protected function getDocumentProviderSource() {
		$this->setFileRepoFile();
		$fileBackend = $this->file->getRepo()->getBackend();
		$fsFile = $fileBackend->getLocalReference([
			'src' => $this->file->getPath()
		]);

		if( $fsFile === null ) {
			throw new \Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found on filesystem!" );
		}

		return new \SplFileInfo( $fsFile->getPath() );
	}

	/**
	 *
	 * @throws \Exception
	 */
	protected function setFileRepoFile() {
		if( $this->file instanceof \File ) {
			return;
		}

		$file = \RepoGroup::singleton()->findFile( $this->getTitle() );
		if( $file === false ) {
			throw new \Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found in any repo!" );
		}
		$this->file = $file;
	}
}