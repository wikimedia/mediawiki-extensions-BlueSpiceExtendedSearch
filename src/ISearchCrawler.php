<?php

namespace BS\ExtendedSearch;

interface ISearchCrawler {

	public function crawl();

	/**
	 *
	 * @return int
	 */
	public function getNumberOfPendingJobs(): int;

	/**
	 *
	 * @return bool
	 */
	public function clearPendingJobs(): bool;
}
