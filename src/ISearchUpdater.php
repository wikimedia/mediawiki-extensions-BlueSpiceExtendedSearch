<?php

namespace BS\ExtendedSearch;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

interface ISearchUpdater {
	/**
	 * Initialize the updater at a safe time
	 *
	 * @param MediaWikiServices $services
	 *
	 * @return void
	 */
	public function init( MediaWikiServices $services ): void;

	/**
	 *
	 * @param Title $title
	 * @param array|null $params
	 *
	 * @return void
	 */
	public function addUpdateJob( Title $title, ?array $params = [] );

	/**
	 * @param Title $title
	 * @param array|null $params
	 *
	 * @return mixed
	 */
	public function makeJob( Title $title, ?array $params = [] );
}
