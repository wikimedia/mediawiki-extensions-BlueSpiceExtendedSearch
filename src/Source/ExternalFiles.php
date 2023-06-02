<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\ISearchCrawler;
use BS\ExtendedSearch\ISearchResultFormatter;
use BS\ExtendedSearch\Source\Crawler\ExternalFile as ExternalFileCrawler;
use BS\ExtendedSearch\Source\Formatter\ExternalFileFormatter;

class ExternalFiles extends Files {

	/**
	 *
	 * @return ExternalFileCrawler
	 */
	public function getCrawler(): ISearchCrawler {
		return $this->makeObjectFromSpec( [
			'class' => ExternalFileCrawler::class,
			'args' => [ $this->config ],
			'services' => [ 'DBLoadBalancer', 'JobQueueGroup', 'TitleFactory', 'ConfigFactory' ]
		] );
	}

	/**
	 * @return ExternalFileFormatter
	 */
	public function getFormatter(): ISearchResultFormatter {
		return $this->makeObjectFromSpec( [
			'class' => ExternalFileFormatter::class,
			'args' => [ $this ]
		] );
	}

	/**
	 * @return string
	 */
	public function getSearchPermission(): string {
		return 'extendedsearch-search-externalfile';
	}
}
