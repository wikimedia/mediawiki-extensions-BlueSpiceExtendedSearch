<?php

namespace BS\ExtendedSearch\Source\Crawler;

use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Title\Title;
use RepoGroup;
use Wikimedia\Rdbms\ILoadBalancer;

class RepoFile extends File {
	/** @var string */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateRepoFile';

	/**
	 *
	 * @var RepoGroup
	 */
	protected $repoGroup = null;

	/**
	 * @param ILoadBalancer $lb
	 * @param RepoGroup $repoGroup
	 * @param JobQueueGroup $jobQueueGroup
	 * @param Config $sourceConfig
	 */
	public function __construct(
		ILoadBalancer $lb, RepoGroup $repoGroup, JobQueueGroup $jobQueueGroup, Config $sourceConfig
	) {
		parent::__construct( $lb, $jobQueueGroup, $sourceConfig );
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @return void
	 */
	public function crawl() {
		$dbr = $this->lb->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			$this->makeQueryConditions(),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::newFromID( $row->page_id );
			$file = $this->repoGroup->findFile( $title );
			if ( $file instanceof \LocalFile === false ) {
				continue;
			}

			if ( $this->shouldSkip( $file ) ) {
				continue;
			}

			$this->addToJobQueue( $title );
		}
	}

	/**
	 * @return array
	 */
	protected function makeQueryConditions() {
		return [
			'page_namespace' => NS_FILE
		];
	}
}
