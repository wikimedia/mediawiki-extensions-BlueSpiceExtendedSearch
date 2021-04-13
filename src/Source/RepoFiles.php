<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\Crawler\RepoFile as RepoFileCrawler;
use BS\ExtendedSearch\Source\DocumentProvider\RepoFile as RepoFileDocumentProvider;
use BS\ExtendedSearch\Source\Formatter\RepoFileFormatter;
use BS\ExtendedSearch\Source\MappingProvider\RepoFile as RepoFileMappingProvider;
use BS\ExtendedSearch\Source\Updater\RepoFile as RepoFileUpdater;

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
	 * @return RepoFileDocumentProvider
	 */
	public function getDocumentProvider() {
		return new RepoFileDocumentProvider(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 *
	 * @return RepoFileMappingProvider
	 */
	public function getMappingProvider() {
		return new RepoFileMappingProvider(
			$this->oDecoratedSource->getMappingProvider()
		);
	}

	/**
	 * @return RepoFileUpdater
	 */
	public function getUpdater() {
		return new RepoFileUpdater( $this );
	}

	/**
	 * @return RepoFileFormatter
	 */
	public function getFormatter() {
		return new RepoFileFormatter( $this );
	}

	/**
	 * @return string
	 */
	public function getSearchPermission() {
		return 'extendedsearch-search-repofile';
	}

}
