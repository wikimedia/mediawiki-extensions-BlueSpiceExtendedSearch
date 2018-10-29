<?php

namespace BS\ExtendedSearch\Tests;

class SourceTest extends \MediaWikiTestCase {
	public function testBackendSources() {
		$backend = \BS\ExtendedSearch\Backend::instance();;
		$this->assertInstanceOf( '\BS\ExtendedSearch\Backend' , $backend );

		$sources = $backend->getSources();
		foreach( $sources as $sourceKey => $source ) {
			$this->assertInstanceOf( '\BS\ExtendedSearch\Source\Base' , $source );
		}
	}
}