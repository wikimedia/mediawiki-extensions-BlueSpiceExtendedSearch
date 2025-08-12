<?php

namespace BS\ExtendedSearch\HookHandler;

use BS\ExtendedSearch\MediaWiki\Specials\SearchCenter;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
class OverrideSpecialSearch implements SpecialPage_initListHook {
	public function __construct(
		private readonly ConfigFactory $configFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialPage_initList( &$list ) {
		$config = $this->configFactory->makeConfig( 'bsg' );
		if ( $config->get( 'ESOverrideSpecialSearch' ) ) {
			$list['Search'] = [ 'class' => SearchCenter::class ];
		}
		return true;
	}
}
