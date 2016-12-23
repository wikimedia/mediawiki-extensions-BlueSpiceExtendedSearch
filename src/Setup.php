<?php

namespace BS\ExtendedSearch;

class Setup {

	/**
	 * Factory for Config object
	 * @return \GlobalVarConfig
	 */
	public static function makeConfig() {

		/**
		 * Unfortunately changing complex settings from 'extension.json'
		 * in 'LocalSettings.php' is problematic. Therefore we provide a hook
		 * point to change settings
		 */
		\Hooks::run( 'BSExtendedSearchMakeConfig', [ &$GLOBALS['bsgESBackends'] ] );
		return new \GlobalVarConfig( 'bsgES' );
	}

	/**
	 * ExtensionFunction callback to wire up all updaters
	 */
	public static function init() {
		$aSources = \BS\ExtendedSearch\Backend::instance( 'local' )->getSources();
		foreach( $aSources as $oSource ) {
			$oSource->getUpdater()->init( $GLOBALS['wgHooks'] );
		}
	}

	/**
	 * Register PHP Unit Tests with MediaWiki framework
	 * @param array $paths
	 * @return boolean
	 */
	public static function onUnitTestsList( &$paths ) {
		$paths[] =  __DIR__ . '/tests/phpunit/';
		return true;
	}
}