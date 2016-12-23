<?php

namespace BS\ExtendedSearch\Source\Crawler;

class WikiPage extends Base {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateWikiPage';

	public function crawl() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page',
			'*',
			$this->makeQueryConditions()
		);
		$aTitles = \TitleArray::newFromResult( $res );

		foreach( $aTitles as $oTitle ) {
			$this->addToJobQueue( $oTitle );
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
