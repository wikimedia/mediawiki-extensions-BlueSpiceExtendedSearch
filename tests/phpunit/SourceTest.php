<?php

namespace BS\ExtendedSearch\Tests;

class SourceTest extends \MediaWikiTestCase {
	public function testBackendSources() {
		$backend = \BS\ExtendedSearch\Backend::instance();
		foreach ( $backend->getSources() as $key => $source ) {
			$this->assertInstanceOf( '\BS\ExtendedSearch\Source\Base', $source );
		}
	}
}
