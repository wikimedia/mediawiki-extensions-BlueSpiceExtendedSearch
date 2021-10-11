<?php

namespace BS\ExtendedSearch\Tests;

class MappingProviderTest extends \MediaWikiIntegrationTestCase {
	/**
	 * @covers \BS\ExtendedSearch\Source\MappingProvider\Base::getPropertyConfig
	 */
	public function testBaseMappingProvider() {
		$oMP = new \BS\ExtendedSearch\Source\MappingProvider\Base();
		$aPC = $oMP->getPropertyConfig();

		$this->assetBaseMappingProviderKeysArePresent( $aPC );
	}

	/**
	 * @covers \BS\ExtendedSearch\Source\MappingProvider\Base::getPropertyConfig
	 */
	public function testMappingProviderDecorators() {
		$aClasses = [ 'WikiPage', 'SpecialPage', 'File' ];
		foreach ( $aClasses as $aBaseClassName ) {
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
