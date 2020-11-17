<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\Crawler\RepoFile as RepoFileCrawler;
use BS\ExtendedSearch\Source\DocumentProvider\File as FileDocumentProvider;
use BS\ExtendedSearch\Source\Updater\RepoFile as RepoFileUpdater;
use BS\ExtendedSearch\Source\Formatter\FileFormatter;

class RepoFiles extends Files {

	/**
	 *
	 * @return RepoFileCrawler
	 */
	public function getCrawler() {
		return new Crawler\RepoFile( $this->getConfig() );
	}

	/**
	 *
	 * @return FileDocumentProvider
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\File(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 * @return RepoFileUpdater
	 */
	public function getUpdater() {
		return new RepoFileUpdater( $this );
	}

	/**
	 * @return FileFormatter
	 */
	public function getFormatter() {
		return new FileFormatter( $this );
	}

	/**
	 * @return string
	 */
	public function getSearchPermission() {
		return 'extendedsearch-search-repofile';
	}

	/**
	 * @return array [ 'type' => [ 'modifierName1', 'modifierName2' ] ]
	 */
	protected function getAvailableLookupModifiers() {
		$modifiers = parent::getAvailableLookupModifiers();
		$modifiers[Backend::QUERY_TYPE_SEARCH][] = 'file-content';
		return $modifiers;
	}
}
