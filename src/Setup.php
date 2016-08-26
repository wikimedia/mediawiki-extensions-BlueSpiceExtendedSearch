<?php

namespace BS\ExtendedSearch;

class Setup {

	/**
	 * Factory for Config object
	 * @return \GlobalVarConfig
	 */
	public static function makeConfig() {
		return new \GlobalVarConfig( 'bsgES' );
	}

	/**
	 * ExtensionFunction callback to wire up all updaters
	 */
	public static function init() {
		$aSources = \BS\ExtendedSearch\Indices::factory('local')->getSources();
		foreach( $aSources as $oSource ) {
			$oSource->getUpdater()->init( $GLOBALS['wgHooks'] );
		}
	}

	/**
	 * Register PHP Unit Tests with MediaWiki frameweork
	 * @param array $paths
	 * @return boolean
	 */
	public static function onUnitTestsList( &$paths ) {
		$paths[] =  __DIR__ . '/tests/phpunit/';
		return true;
	}
}