<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\ISearchCrawler;
use BS\ExtendedSearch\ISearchDocumentProvider;
use BS\ExtendedSearch\ISearchMappingProvider;
use BS\ExtendedSearch\ISearchResultFormatter;
use BS\ExtendedSearch\ISearchUpdater;
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
	public function getCrawler(): ISearchCrawler {
		return $this->makeObjectFromSpec( [
			'class' => RepoFileCrawler::class,
			'args' => [ $this->config ],
			'services' => [ "DBLoadBalancer", "RepoGroup", 'JobQueueGroup' ]
		] );
	}

	/**
	 *
	 * @return RepoFileDocumentProvider
	 */
	public function getDocumentProvider(): ISearchDocumentProvider {
		return $this->makeObjectFromSpec( [
			'class' => RepoFileDocumentProvider::class,
			'services' => [ 'MimeAnalyzer' ]
		] );
	}

	/**
	 *
	 * @return RepoFileMappingProvider
	 */
	public function getMappingProvider(): ISearchMappingProvider {
		return new RepoFileMappingProvider();
	}

	/**
	 * @return RepoFileUpdater
	 */
	public function getUpdater(): ISearchUpdater {
		return new RepoFileUpdater( $this );
	}

	/**
	 * @return RepoFileFormatter
	 */
	public function getFormatter(): ISearchResultFormatter {
		return new RepoFileFormatter( $this );
	}

	/**
	 * @return string
	 */
	public function getSearchPermission(): string {
		return 'extendedsearch-search-repofile';
	}

}
