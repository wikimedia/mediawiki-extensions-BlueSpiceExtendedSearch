<?php

namespace BS\ExtendedSearch\Tests;

use BS\ExtendedSearch\Wildcarder;
use MediaWikiTestCase;

class WildcarderTest extends MediaWikiTestCase {
	public function testWildcardSingleWord() {
		$origin = 'test';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "(*test OR test*)", $result );
	}

	public function testWildcardMultiWord() {
		$origin = 'Test text';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "(*Test OR Test*) (*text OR text*)", $result );
	}

	public function testWildcardMultiWordWithSeparators() {
		$origin = '   Test-text;   dummy   '; // Extra spaces
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "(*Test OR Test*) (*text OR text*) (*dummy OR dummy*)", $result );
	}

	public function testWildcardRegexSimple() {
		$origin = 'dummy*';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "dummy*", $result );
	}

	public function testWildcardRegexComplex() {
		$origin = '/test|dumm(y|ies)\s/';
		$wildcarder = Wildcarder::factory( $origin );
		$result = $wildcarder->getWildcarded();
		$this->assertEquals( "/test|dumm(y|ies)\s/", $result );
	}
}