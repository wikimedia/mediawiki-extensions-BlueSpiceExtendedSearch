<?php

namespace BS\ExtendedSearch\Tests;

class MappingProviderTest extends \MediaWikiTestCase {
	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testBaseMappingProvider() {
		$oMP = new \BS\ExtendedSearch\Source\MappingProvider\Base();
		$aPC = $oMP->getPropertyConfig();

		$this->assetBaseMappingProviderKeysArePresent( $aPC );
	}

	public function testMappingProviderDecorators() {
		$aClasses = [ 'WikiPage', 'SpecialPage', 'File' ];
		foreach( $aClasses as $aBaseClassName ) {
			$sClassName = "\\BS\\ExtendedSearch\\Source\\MappingProvider\\$aBaseClassName";
			$oDecMP = new $sClassName(
				new \BS\ExtendedSearch\Source\MappingProvider\Base()
			);
			$aPC = $oDecMP->getPropertyConfig();
			$this->assetBaseMappingProviderKeysArePresent( $aPC );
		}
	}

	public function assetBaseMappingProviderKeysArePresent( $aPC ) {
		$this->assertArrayHasKey( 'uri', $aPC );
		$this->assertArrayHasKey( 'basename', $aPC );
		$this->assertArrayHasKey( 'extension', $aPC );
		$this->assertArrayHasKey( 'mime_type', $aPC );
		$this->assertArrayHasKey( 'mtime', $aPC );
		$this->assertArrayHasKey( 'ctime', $aPC );
		$this->assertArrayHasKey( 'size', $aPC );
		$this->assertArrayHasKey( 'tags', $aPC );
	}

}