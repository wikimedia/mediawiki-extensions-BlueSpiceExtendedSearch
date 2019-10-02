<?php

namespace BS\ExtendedSearch;

use BlueSpice\Services;
use BS\ExtendedSearch\Backend as SearchBackend;
use BS\ExtendedSearch\MediaWiki\Backend\BlueSpiceSearch;
use SpecialPage;

class Setup {
	/**
	 * ExtensionFunction callback to wire up all updaters
	 */
	public static function init() {
		$sources = SearchBackend::instance()->getSources();
		foreach ( $sources as $source ) {
			$source->getUpdater()->init( $GLOBALS['wgHooks'] );
		}

		// Set ExtendedSearch backend as default MW engine
		$GLOBALS['wgSearchType'] = BlueSpiceSearch::class;
	}

	// TODO: Move hooks to proper classes

	/**
	 * Register QUnit Tests with MediaWiki framework
	 * @param array $testModules
	 * @param \ResourceLoader $resourceLoader
	 * @return bool
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

	/**
	 * @param \Skin $skin
	 * @param \SkinTemplate $template
	 * @return bool
	 * @throws \ConfigException
	 * @throws \MWException
	 */
	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template ) {
		$template->set( 'bs_search_id', 'bs-extendedsearch-box' );
		$template->set(
			'bs_search_input',
			[
				'id' => 'bs-extendedsearch-input',
				'type' => 'text',
				'name' => 'raw_term',
				'placeholder' => wfMessage( 'bs-extendedsearch-search-input-placeholder' )->plain(),
				'aria-label' => wfMessage( 'bs-extendedsearch-search-input-aria-label' )->plain()
			]
		);

		$template->set( 'bs_search_method', 'GET' );

		$template->set( 'bs_search_mobile_id', 'bs-extendedsearch-mobile-box' );
		$template->set(
			'bs_search_mobile_input',
			[
				'id' => 'bs-extendedsearch-mobile-input',
				'type' => 'text',
				'name' => 'raw_term',
				'placeholder' => wfMessage( 'bs-extendedsearch-search-input-placeholder' )->plain(),
				'aria-label' => wfMessage( 'bs-extendedsearch-search-input-aria-label' )->plain()
			]
		);

		$template->set( 'bs_search_target', [
			'page_name' => SpecialPage::getTitleFor( 'BSSearchCenter' )->getPrefixedDBkey()
		] );

		$template->set(
			'bs_search_action',
			$skin->getConfig()->get( 'Script' )
		);
		return true;
	}

	public static function getSearchEngineClass( $db ) {
		$seFactory = Services::getInstance()->getSearchEngineFactory();
		return $seFactory::getSearchEngineClass( $db );
	}

	/**
	 * Add parser and definition to GLOBALS wgParamDefinitions
	 */
	public static function onRegistration() {
		$GLOBALS['wgParamDefinitions']['searchresulttypelist'] = [
			'definition' => "\\BS\\ExtendedSearch\\Param\\Definition\\SearchResultTypeListParam",
			'string-parser' => "\\BS\\ExtendedSearch\\Param\\Parser\\SearchResultTypeParser"
		];
	}
}
