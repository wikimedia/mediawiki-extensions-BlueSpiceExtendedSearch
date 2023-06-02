<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\ISearchCrawler;
use BS\ExtendedSearch\ISearchDocumentProvider;
use BS\ExtendedSearch\ISearchMappingProvider;
use BS\ExtendedSearch\ISearchResultFormatter;

class SpecialPages extends GenericSource {

	/**
	 *
	 * @return ISearchCrawler
	 */
	public function getCrawler(): ISearchCrawler {
		return $this->makeObjectFromSpec( [
			'class' => Crawler\SpecialPage::class,
			'args' => [ $this->config ],
			'services' => [ 'DBLoadBalancer', 'JobQueueGroup', 'SpecialPageFactory' ]
		] );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\SpecialPage
	 */
	public function getDocumentProvider(): ISearchDocumentProvider {
		return new DocumentProvider\SpecialPage();
	}

	/**
	 *
	 * @return MappingProvider\SpecialPage
	 */
	public function getMappingProvider(): ISearchMappingProvider {
		return new MappingProvider\SpecialPage();
	}

	/**
	 *
	 * @return Formatter\SpecialPageFormatter
	 */
	public function getFormatter(): ISearchResultFormatter {
		return new Formatter\SpecialPageFormatter( $this );
	}

	/**
	 *
	 * @return string
	 */
	public function getSearchPermission(): string {
		return 'extendedsearch-search-specialpage';
	}
}
