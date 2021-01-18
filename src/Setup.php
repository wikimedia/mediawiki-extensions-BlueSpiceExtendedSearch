<?php

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\MediaWiki\Backend\BlueSpiceSearch;
use MediaWiki\MediaWikiServices;
use QuickTemplate;
use SpecialPage;
use Wikimedia\Rdbms\ILoadBalancer;

class Setup {
	/**
	 * ExtensionFunction callback to wire up all updaters
	 */
	public static function init() {
		$sources = MediaWikiServices::getInstance()
			->getService( 'BSExtendedSearchBackend' )->getSources();
		foreach ( $sources as $source ) {
			$source->getUpdater()->init(
				MediaWikiServices::getInstance()->getHookContainer()
			);
		}

		// Set ExtendedSearch backend as default MW engine
		$GLOBALS['wgSearchType'] = BlueSpiceSearch::class;
	}

	// TODO: Move hooks to proper classes

	/**
	 * @param \Skin &$skin
	 * @param QuickTemplate &$template
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

	/**
	 *
	 * @param ILoadBalancer $lb
	 * @return \SearchEngine
	 */
	public static function getSearchEngineClass( ILoadBalancer $lb ) {
		$seFactory = MediaWikiServices::getInstance()->getSearchEngineFactory();
		return $seFactory::getSearchEngineClass( $lb );
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
