<?php

namespace BS\ExtendedSearch\Tests;

use BS\ExtendedSearch\Wildcarder;
use MediaWikiIntegrationTestCase;

class WildcarderTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers \BS\ExtendedSearch\Wildcarder::getWildcarded
	 */
	public function testWildcardSingleWord() {
		$origin = 'test';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "(*test OR test* OR *test*)", $result );
	}

	/**
	 * @covers \BS\ExtendedSearch\Wildcarder::getWildcarded
	 */
	public function testWildcardMultiWord() {
		$origin = 'Test text';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "(*Test OR Test* OR *Test*) (*text OR text* OR *text*)", $result );
	}

	/**
	 * @covers \BS\ExtendedSearch\Wildcarder::getWildcarded
	 */
	public function testWildcardMultiWordWithSeparators() {
		// Extra spaces
		$origin = '   Test-text;   dummy   ';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals(
			"(*Test OR Test* OR *Test*) (*text OR text* OR *text*) (*dummy OR dummy* OR *dummy*)",
			$result
		);
	}

	/**
	 * @covers \BS\ExtendedSearch\Wildcarder::getWildcarded
	 */
	public function testWildcardRegexSimple() {
		$origin = 'dummy*';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "dummy*", $result );
	}

	/**
	 * @covers \BS\ExtendedSearch\Wildcarder::getWildcarded
	 */
	public function testWildcardRegexComplex() {
		$origin = '/test|dumm(y|ies)\s/';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "/test|dumm(y|ies)\s/", $result );
	}
}
