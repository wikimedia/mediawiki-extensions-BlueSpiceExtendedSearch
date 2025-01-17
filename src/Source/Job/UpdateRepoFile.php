<?php

namespace BS\ExtendedSearch\Source\Job;

use File;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class UpdateRepoFile extends UpdateTitleBase {
	/** @inheritDoc */
	protected $sSourceKey = 'repofile';
	/** @var File|null */
	protected $file = null;
	/** @var array */
	protected $fileData = [];
	/** @var string|null */
	protected $canonicalURL = null;

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		if ( isset( $params['filedata'] ) ) {
			$this->fileData = $params['filedata'];
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
		return $this->fileData['canonicalUrl'] ?? $this->canonicalURL;
	}

	/**
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getDocumentProviderSource() {
		$this->setFileRepoFile();

		if ( isset( $this->fileData['fsFile'] ) ) {
			$fsFile = $this->fileData['fsFile'];
		} elseif ( $this->file ) {
			$this->setFileRepoFile();
			$fileBackend = $this->file->getRepo()->getBackend();
			$fsFile = $fileBackend->getLocalReference( [
				'src' => $this->file->getPath()
			] );

			if ( $fsFile === null ) {
				throw new \Exception(
					"File '{$this->getTitle()->getPrefixedDBkey()}' not found on filesystem!"
				);
			}
		}

		if ( $fsFile instanceof \FSFile ) {
			return [
				'fsFile' => new \SplFileInfo( $fsFile->getPath() ),
				'title' => $this->title->getDBkey()
			];
		}

		throw new \Exception( "FSFile cannot be created" );
	}

	/**
	 *
	 * @throws \Exception
	 */
	protected function setFileRepoFile() {
		if ( !empty( $this->fileData ) ) {
			return;
		}
		if ( $this->file instanceof \File ) {
			return;
		}

		$services = MediaWikiServices::getInstance();
		$file = $services->getRepoGroup()->findFile( $this->getTitle() );
		if ( $file === false ) {
			throw new \Exception(
				"File '{$this->getTitle()->getPrefixedDBkey()}' not found in any repo!"
			);
		}
		$this->canonicalURL = $file->getCanonicalURL();
		$hookContainer = $services->getHookContainer();
		$hookContainer->run( 'BSExtendedSearchRepoFileGetFile', [
			&$file
		] );

		$this->file = $file;
	}

	/**
	 *
	 * @return bool
	 */
	protected function isDeletion() {
		return !empty( $this->fileData );
	}

	public function __destruct() {
		$this->file = null;
		$this->fileData = [];
		unset( $this->file );
	}
}
