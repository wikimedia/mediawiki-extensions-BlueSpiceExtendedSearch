<?php

namespace BS\ExtendedSearch;

class Setup {

	public static function makeConfig() {
		return new \GlobalVarConfig( 'bsgES' );
	}

	/**
	 * Wire up all updaters
	 */
	public static function init() {
		$aSources = \BS\ExtendedSearch\Indices::factory('local')->getSources();
		foreach( $aSources as $oSource ) {
			$oSource->getUpdater()->init( $GLOBALS['wgHooks'] );
		}
	}
}