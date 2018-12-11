<?php

namespace BS\ExtendedSearch\Source\Crawler;

use MediaWiki\MediaWikiServices;

class WikiPage extends Base {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateWikiPage';

	public function crawl() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page', 'page_props' ],
			[ 'page_id', "GROUP_CONCAT( pp_propname SEPARATOR '|' ) as prop_names" ],
			$this->makeQueryConditions(),
			__METHOD__,
			[ 'GROUP BY' => 'page_id' ],
			[ 'page_props' => [ 'LEFT OUTER JOIN', [ 'page_id=pp_page' ] ] ]
		);

		foreach( $res as $row ) {
			$title = \Title::newFromID( $row->page_id );

			// Not ideal, but beats running page_props query for each page
			$props = explode( '|', $row->prop_names );
			$props = array_unique( $props );
			if( in_array( 'noindex', $props ) ) {
				continue;
			}
			$this->addToJobQueue( $title );
		}
	}

	protected function makeQueryConditions() {
		$aConds = [];

		if( $this->oConfig->has( 'skip_namespaces' ) ) {
			$aAllNamespaces = \RequestContext::getMain()->getLanguage()->getNamespaceIds();
			$aOnlyIn = array_diff( $aAllNamespaces, $this->oConfig->get( 'skip_namespaces' ) );
			$aConds['page_namespace'] = $aOnlyIn;
		}

		return $aConds;
	}
}
