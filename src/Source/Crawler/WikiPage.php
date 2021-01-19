<?php

namespace BS\ExtendedSearch\Source\Crawler;

use MediaWiki\MediaWikiServices;

class WikiPage extends Base {
	/** @var Wikimedia\Rdbms */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateWikiPage';

	public function crawl() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			$this->makeQueryConditions(),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = \Title::newFromID( $row->page_id );
			if ( $title === null ) {
				continue;
			}
			$this->addToJobQueue( $title );
		}
	}

	protected function makeQueryConditions() {
		$aConds = [];

		if ( $this->oConfig->has( 'skip_namespaces' ) ) {
			$aAllNamespaces = \RequestContext::getMain()->getLanguage()->getNamespaceIds();
			$aOnlyIn = array_diff( $aAllNamespaces, $this->oConfig->get( 'skip_namespaces' ) );
			$aConds['page_namespace'] = $aOnlyIn;
		}

		return $aConds;
	}
}
