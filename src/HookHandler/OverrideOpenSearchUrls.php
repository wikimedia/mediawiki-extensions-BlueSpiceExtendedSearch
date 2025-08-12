<?php

namespace BS\ExtendedSearch\HookHandler;

use MediaWiki\Config\ConfigFactory;
use MediaWiki\Hook\OpenSearchUrlsHook;
use MediaWiki\SpecialPage\SpecialPage;

class OverrideOpenSearchUrls implements OpenSearchUrlsHook {
	public function __construct(
		private readonly ConfigFactory $configFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onOpenSearchUrls( &$urls ) {
		$config = $this->configFactory->makeConfig( 'bsg' );
		if ( $config->get( 'ESOverrideSpecialSearch' ) ) {
			foreach ( $urls as $key => $url ) {
				if ( isset( $url['type'] ) && $url['type'] === 'text/html' ) {
					unset( $urls[$key] );
				}
			}
			$searchPage = SpecialPage::getTitleFor( 'BSSearchCenter' );
			$urls[] = [
				'type' => 'text/html',
				'method' => 'get',
				'template' => $searchPage->getCanonicalURL( 'q={searchTerms}' )
			];
		}
	}
}
