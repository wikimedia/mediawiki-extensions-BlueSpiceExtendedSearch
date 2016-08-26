<?php

namespace BS\ExtendedSearch\Source;

class SpecialPages extends DecoratorBase {
	public function getTypeKey() {
		return 'specialpage';
	}

	public function getCrawler() {
		return new Crawler\SpecialPage();
	}
}