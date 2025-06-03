<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

use BS\ExtendedSearch\Plugin\ISearchContextProvider;
use BS\ExtendedSearch\PluginManager;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\Title;

class AddResources implements BeforePageDisplayHook {

	public function __construct(
		private readonly NamespaceInfo $namespaceInfo,
		private readonly ConfigFactory $configFactory,
		private readonly PluginManager $pluginManager
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( !$out->getTitle() || $out->getTitle()->isSpecial( 'BSSearchCenter' ) ) {
			return;
		}
		$out->addJsConfigVars(
			'ESMasterFilter',
			$this->getMasterFilter( $out )
		);

		$out->addModules( [ 'ext.blueSpiceExtendedSearch.SearchFieldAutocomplete' ] );

		$contexts = [];
		/** @var ISearchContextProvider[] $contextProviders */
		$contextProviders = $this->pluginManager->getPluginsImplementing( ISearchContextProvider::class );
		foreach ( $contextProviders as $contextProvider ) {
			$definition = $contextProvider->getContextDefinitionForPage( $out->getTitle(), $out->getUser() );
			if ( $definition === null ) {
				continue;
			}
			$contexts[$contextProvider->getContextKey()] = [
				'text' => $contextProvider->getContextDisplayText(
					$definition, $out->getUser(), $out->getLanguage()
				)->parse(),
				'showCustomPill' => $contextProvider->showContextFilterPill(),
				'definition' => json_encode( $definition ),
				'priority' => $contextProvider->getContextPriority(),
			];
		}
		// Sort contexts by priority
		uasort( $contexts, static function ( $a, $b ) {
			return $a['priority'] <=> $b['priority'];
		} );
		$out->addJsConfigVars( 'ESContexts', $contexts );
	}

	/**
	 * @return array|null
	 */
	private function getMasterFilter( OutputPage $out ) {
		if (
			!$this->namespaceInfo->hasSubpages( $out->getTitle()->getNamespace() )
		) {
			// Disable if NS does not support subpages
			return null;
		}
		$config = $this->configFactory->makeConfig( 'bsg' );
		$patterns = $config->get( 'ESSubpageMasterFilterPatterns' );
		if ( !$patterns ) {
			return null;
		}

		$match = false;
		foreach ( $patterns as $pattern ) {
			$regex = "/^" . $pattern . "/";
			if (
				!preg_match( $regex, $out->getTitle()->getPrefixedDBkey() ) &&
				!preg_match( $regex, $out->getTitle()->getPrefixedText() )
			) {
				continue;
			}
			$match = true;
			break;
		}

		if ( !$match ) {
			return null;
		}

		$title = $out->getTitle()->getDBkey();
		if ( $config->get( 'ESSubpageMasterFilterUseRootOnly' ) ) {
			$titleBits = explode( '/', $out->getTitle()->getDBkey() );
			$text = array_shift( $titleBits );
			$newTitle = Title::makeTitle( $out->getTitle()->getNamespace(), $text );
			$title = $newTitle->getDBkey();
		}

		$namespace = $out->getTitle()->getNamespace();
		if ( $namespace === NS_MAIN ) {
			$namespaceText = Message::newFromKey( 'bs-ns_main' )->text();
		} else {
			$namespaceText = MediaWikiServices::getInstance()->getNamespaceInfo()
				->getCanonicalName( $namespace );
		}

		return [
			'title' => $title,
			'namespace' => [
				'id' => $out->getTitle()->getNamespace(),
				'text' => $namespaceText,
			],
		];
	}
}
