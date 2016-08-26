<?php

namespace BS\ExtendedSearch\Source\Crawler;

class WikiPage extends Base {
	public function crawl() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'page', '*' );
		$aTitles = \TitleArray::newFromResult($res);
return;
		foreach( $aTitles as $oTitle ) {
			\JobQueueGroup::singleton()->push(
				new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $oTitle, [] )
			);
		}
	}
}
