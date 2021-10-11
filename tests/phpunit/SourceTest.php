<?php

namespace BS\ExtendedSearch\Tests;

use BS\ExtendedSearch\Backend;

class SourceTest extends \MediaWikiIntegrationTestCase {
	/**
	 * @covers \BS\ExtendedSearch\Backend::getSources
	 */
	public function testBackendSources() {
		$backend = Backend::instance();
		$this->assertInstanceOf( '\BS\ExtendedSearch\Backend', $backend );
		$sources = $backend->getSources();
		foreach ( $sources as $key => $source ) {
			$this->assertInstanceOf( '\BS\ExtendedSearch\Source\Base', $source );
		}
	}
}
