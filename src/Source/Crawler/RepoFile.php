<?php

namespace BS\ExtendedSearch\Source\Crawler;

use MediaWiki\MediaWikiServices;

class RepoFile extends File {
	/** @var Wikimedia\Rdbms */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateRepoFile';

	public function crawl() {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			$this->makeQueryConditions()
		);

		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();
		foreach ( $res as $row ) {
			$title = \Title::newFromID( $row->page_id );
			$file = $repoGroup->findFile( $title );
			if ( $file instanceof \LocalFile === false ) {
				continue;
			}

			if ( $this->shouldSkip( $file ) ) {
				continue;
			}

			$this->addToJobQueue( $title );
		}
	}

	protected function makeQueryConditions() {
		return [
			'page_namespace' => NS_FILE
		];
	}
}
