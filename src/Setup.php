<?php

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\Backend as SearchBackend;

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
		$sources = SearchBackend::instance()->getSources();
		foreach( $sources as $source ) {
			$source->getUpdater()->init( $GLOBALS['wgHooks'] );
		}
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
				'name' => 'raw_term'
			)
		);

		$template->set( 'bs_search_method', 'POST' );

		$template->set( 'bs_search_mobile_id', 'bs-extendedsearch-mobile-box' );
		$template->set(
			'bs_search_mobile_input',
			array(
				'id' => 'bs-extendedsearch-mobile-input',
				'type' => 'text',
				'name' => 'raw_term'
			)
		);

		$template->set( 'bs_search_action', \SpecialPage::getTitleFor( 'SearchCenter' )->getLocalURL() );
		$template->set(
			'bs_search_target',
			[]
		);
		return true;
	}

	public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
		$title = $out->getTitle();
		if( $title != \SpecialPage::getTitleFor( 'BSSearchCenter' ) ) {
			$out->addModules( "ext.blueSpiceExtendedSearch.SearchFieldAutocomplete" );
		}

		$autocompleteConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );
		$sourceIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSourceIcons' );

		$out->addJsConfigVars( 'bsgESAutocompleteConfig', $autocompleteConfig );
		$out->addJsConfigVars( 'bsgESSourceIcons', $sourceIcons );
	}
}
