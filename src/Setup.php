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
		$paths[] =  dirname( __DIR__ ) . '/tests/phpunit/';
		return true;
	}

	//TODO: Move hooks to proper classes

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
				'tests/qunit/bs.extendedSearch.Lookup.test.js'
			],
			'dependencies' => [
				'ext.blueSpiceExtendedSearch'
			],
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'BlueSpiceExtendedSearch',
		];

		return true;
	}

	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template ) {
		$template->set( 'bs_search_id', 'bs-extendedsearch-box' );
		$template->set(
			'bs_search_input',
			array(
				'id' => 'bs-extendedsearch-input',
				'type' => 'text',
				'name' => 'q'
			)
		);

		$template->set( 'bs_search_mobile_id', 'bs-extendedsearch-mobile-box' );
		$template->set(
			'bs_search_mobile_input',
			array(
				'id' => 'bs-extendedsearch-mobile-input',
				'type' => 'text',
				'name' => 'q'
			)
		);

		$template->set(
			'bs_search_target',
			array(
				'page_name' => \SpecialPage::getTitleFor( 'SearchCenter' )->getFullText()
			)
		);
		return true;
	}

	public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
		$out->addModules( "ext.blueSpiceExtendedSearch.Autocomplete" );
		//$out->addModules( "ext.blueSpiceExtendedSearch.Autocomplete.desktop" );
		//$out->addModules( "ext.blueSpiceExtendedSearch.Autocomplete.mobile" );

		$autocompleteConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );
		$sourceIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSourceIcons' );

		$out->addJsConfigVars( 'bsgESAutocompleteConfig', $autocompleteConfig );
		$out->addJsConfigVars( 'bsgESSourceIcons', $sourceIcons );
	}
}
