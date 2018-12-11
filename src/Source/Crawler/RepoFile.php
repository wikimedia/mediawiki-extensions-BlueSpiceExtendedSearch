<?php

namespace BS\ExtendedSearch\Source\Crawler;

use MediaWiki\MediaWikiServices;

class RepoFile extends WikiPage {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateRepoFile';

	public function crawl() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			$this->makeQueryConditions()
		);

		foreach( $res as $row ) {
			$title = \Title::newFromID( $row->page_id );
			$file = wfFindFile( $title );
			if ( $file instanceof \LocalFile === false ) {
				continue;
			}
			if ( $this->oConfig->has( 'extension_blacklist' ) ) {
				$lcExt = strtolower( $file->getExtension() );
				foreach( $this->oConfig->get( 'extension_blacklist' ) as $blacklisted ) {
					if ( $lcExt === strtolower( $blacklisted ) ) {
						continue 2;
					}
				}
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
