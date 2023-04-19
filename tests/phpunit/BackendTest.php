<?php

namespace BS\ExtendedSearch\Tests;

class BackendTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @covers \BS\ExtendedSearch\Backend
	 */
	public function testLocalBackend() {
		$oBackend = $this->getServiceContainer()->getService( 'BSExtendedSearchBackend' );
		$this->assertInstanceOf( '\BS\ExtendedSearch\Backend', $oBackend );
	}
}
