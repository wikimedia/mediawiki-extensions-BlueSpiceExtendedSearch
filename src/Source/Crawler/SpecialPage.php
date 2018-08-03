<?php

namespace BS\ExtendedSearch\Source\Crawler;

class SpecialPage extends Base {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateSpecialPage';

	public function crawl() {
		$aCanonicalNames = \SpecialPageFactory::getNames();
		foreach( $aCanonicalNames as $sCanonicalName ) {
			$this->addToJobQueue( \SpecialPage::getTitleFor( $sCanonicalName ) );
		}
	}
}