<?php

namespace BS\ExtendedSearch\Source\Crawler;

use MediaWiki\Context\RequestContext;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\IDatabase;

class WikiPage extends Base {
	/** @var string */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateWikiPage';

	public function crawl() {
		$dbr = $this->lb->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[ 'page' ],
			[ 'page_id' ],
			$this->makeQueryConditions( $dbr ),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::newFromID( $row->page_id );
			if ( $title === null ) {
				continue;
			}
			$this->addToJobQueue( $title );
		}
	}

	/**
	 * @param IDatabase $db
	 * @return array
	 */
	protected function makeQueryConditions( IDatabase $db ) {
		$aConds = [];

		if ( $this->sourceConfig->has( 'skip_namespaces' ) ) {
			$aAllNamespaces = RequestContext::getMain()->getLanguage()->getNamespaceIds();
			$aOnlyIn = array_diff( $aAllNamespaces, $this->sourceConfig->get( 'skip_namespaces' ) );
			$aConds['page_namespace'] = $aOnlyIn;
		}
		$skipContentModels = $this->sourceConfig->has( 'skip_content_models' ) ?
			$this->sourceConfig->get( 'skip_content_models' ) : [];
		if ( is_array( $skipContentModels ) && !empty( $skipContentModels ) ) {
			$aConds[] = 'page_content_model NOT IN (' . $db->makeList( $skipContentModels ) . ')';
		}

		return $aConds;
	}
}
