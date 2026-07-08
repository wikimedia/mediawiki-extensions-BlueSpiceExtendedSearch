<?php

namespace BS\ExtendedSearch;

interface ISearchCrawler {

	/**
	 * @return void
	 */
	public function crawl();

	/**
	 * @return int
	 */
	public function getNumberOfPendingJobs(): int;

	/**
	 * @return bool
	 */
	public function clearPendingJobs(): bool;
}
