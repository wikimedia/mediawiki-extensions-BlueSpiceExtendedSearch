<?php

namespace BS\ExtendedSearch;

use MediaWiki\MediaWikiServices;
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
				MediaWikiServices::getInstance()
			);
		}
	}

	// TODO: Move hooks to proper classes

	/**
	 *
	 * @param ILoadBalancer $lb
	 * @return \SearchEngine
	 */
	public static function getSearchEngineClass( ILoadBalancer $lb ) {
		$seFactory = MediaWikiServices::getInstance()->getSearchEngineFactory();
		$class = $seFactory::getSearchEngineClass( $lb );
		return new $class( $lb );
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
