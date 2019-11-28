<?php

namespace BS\ExtendedSearch\Tests;

class BackendTest extends \MediaWikiTestCase {

	/**
	 * @covers \BS\ExtendedSearch\Backend::instance
	 */
	public function testLocalBackend() {
		$oBackend = \BS\ExtendedSearch\Backend::instance();
		$this->assertInstanceOf( '\BS\ExtendedSearch\Backend', $oBackend );
	}
}
