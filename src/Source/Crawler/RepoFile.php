<?php

namespace BS\ExtendedSearch\Source\Crawler;

class RepoFile extends File {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateRepoFile';

	public function crawl() {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			$this->makeQueryConditions()
		);

		foreach ( $res as $row ) {
			$title = \Title::newFromID( $row->page_id );
			$file = wfFindFile( $title );
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
