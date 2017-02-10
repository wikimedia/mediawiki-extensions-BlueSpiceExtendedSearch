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

		//WikiAdmin can not register a normal special page yet.
		/*
		\WikiAdmin::registerModuleClass( 'BS\ExtendedSearch\MediaWiki\Specials\SearchAdmin', array(
			'image' => '/extensions/BlueSpiceExtendedSearch/resources/images/bs-btn_searchadmin.png',
			'level' => 'wikiadmin',
			'message' => 'bssearchadmin',
			'iconCls' => 'bs-icon-magnifying-glass'
		) );
		*/
	}

	/**
	 * Adds link to admin panel
	 * @param array $aOutSortable
	 * @return boolean always true to keep hook running
	 */
	public static function onBSWikiAdminMenuItems( &$aOutSortable ) {
		$oSpecialPage = \SpecialPage::getTitleFor( 'BSSearchAdmin' );
		$sLink = \Html::element(
				'a',
				array (
					'id' => 'bs-admin-extenedsearch',
					'href' => $oSpecialPage->getLocalURL(),
					'title' => wfMessage( 'bssearchadmin-desc' )->plain(),
					'class' => 'bs-admin-link bs-icon-magnifying-glass'
				),
				wfMessage( 'bssearchadmin' )->plain()
		);
		$aOutSortable[wfMessage( 'bssearchadmin' )->escaped()] = '<li>' . $sLink . '</li>';
		return true;
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

	/**
	 * Register QUnit Tests with MediaWiki framework
	 * @param array $testModules
	 * @param \ResourceLoader $resourceLoader
	 * @return boolean
	 */
	public static function onResourceLoaderTestModules( array &$testModules, \ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['ext.blueSpiceExtendedSearch.tests'] = [
			'scripts' => [
				'tests/qunit/ext.blueSpiceExtendedSearch.utils.test.js',
				'tests/qunit/bs.extendedSearch.LookUp.test.js'
			],
			'dependencies' => [
				'ext.blueSpiceExtendedSearch'
			],
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'BlueSpiceExtendedSearch',
		];

		return true;
	}
}