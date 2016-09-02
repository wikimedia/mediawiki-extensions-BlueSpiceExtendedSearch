<?php

namespace BS\ExtendedSearch\Tests;

class SourceTest extends \MediaWikiTestCase {
	public function testBackendSources() {
		$aBackends = \BS\ExtendedSearch\Backend::factoryAll();
		foreach( $aBackends as $sBackendKey => $oBackend ) {
			$this->assertInstanceOf( '\BS\ExtendedSearch\Backend' , $oBackend );

			$aSources = $oBackend->getSources();
			foreach( $aSources as $sSourceKey => $oSource ) {
				$this->assertInstanceOf( '\BS\ExtendedSearch\Source\Base' , $oSource );
			}
		}
	}
}