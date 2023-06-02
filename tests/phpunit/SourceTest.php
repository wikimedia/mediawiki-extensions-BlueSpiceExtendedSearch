<?php

namespace BS\ExtendedSearch\Tests;

use BS\ExtendedSearch\ISearchSource;

class SourceTest extends \MediaWikiIntegrationTestCase {
	/**
	 * @covers \BS\ExtendedSearch\Backend::getSources
	 */
	public function testBackendSources() {
		$backend = $this->getServiceContainer()->getService( 'BSExtendedSearchBackend' );
		$this->assertInstanceOf( '\BS\ExtendedSearch\Backend', $backend );
		$sources = $backend->getSources();
		foreach ( $sources as $key => $source ) {
			$this->assertInstanceOf( ISearchSource::class, $source );
		}
	}
}
