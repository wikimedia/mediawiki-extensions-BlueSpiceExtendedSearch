<?php

namespace BS\ExtendedSearch\Source\Crawler;

class SpecialPage extends Base {
	/** @inheritDoc */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateSpecialPage';

	public function crawl() {
		$aCanonicalNames = \MediaWiki\MediaWikiServices::getInstance()
			->getSpecialPageFactory()
			->getNames();
		foreach ( $aCanonicalNames as $sCanonicalName ) {
			$this->addToJobQueue( \SpecialPage::getTitleFor( $sCanonicalName ) );
		}
	}
}
