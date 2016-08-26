<?php

namespace BS\ExtendedSearch\Source\Crawler;

class SpecialPage extends Base {
	public function crawl() {
		$aCononicalNames = \SpecialPageFactory::getNames();
		foreach( $aCononicalNames as $sCanonicalName ) {
			\JobQueueGroup::singleton()->push(
				new \BS\ExtendedSearch\Source\Job\UpdateSpecialPage(
					\SpecialPage::getTitleFor( $sCanonicalName ),
					[]
				)
			);
		}
	}
}