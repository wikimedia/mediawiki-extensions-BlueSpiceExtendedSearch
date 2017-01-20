<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateRepoFile extends UpdateTitleBase {

	protected $sSourceKey = 'repofile';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		parent::__construct( 'updateRepoFileIndex', $title, $params );
	}

	protected function getDocumentProviderUri() {
		$oFile = $this->getFileRepoFile();
		return $oFile->getFullUrl();
	}

	protected function getDocumentProviderSource() {
		$oFile = $this->getFileRepoFile();
		$oFileBackend = $oFile->getRepo()->getBackend();
		$oFSFile = $oFileBackend->getLocalReference([
			'src' => $oFile->getPath()
		]);

		if( $oFSFile === null ) {
			throw new Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found on filesystem!" );
		}

		return new \SplFileInfo( $oFSFile->getPath() );
	}

	/**
	 *
	 * @return \File
	 * @throws Exception
	 */
	protected function getFileRepoFile() {
		$oFile = \RepoGroup::singleton()->findFile( $this->getTitle() );
		if( $oFile === false ) {
			throw new Exception( "File '{$this->getTitle()->getPrefixedDBkey()}' not found in any repo!" );
		}
		return $oFile;
	}

}