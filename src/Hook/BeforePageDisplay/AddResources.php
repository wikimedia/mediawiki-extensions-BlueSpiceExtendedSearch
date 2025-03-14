<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function skipProcessing() {
		if ( !$this->out->getTitle() ) {
			return true;
		}
	}

	protected function doProcess() {
		$title = $this->out->getTitle();
		if ( $title->equals( SpecialPage::getTitleFor( 'BSSearchCenter' ) ) === false ) {
			$this->out->addJsConfigVars(
				'ESMasterFilter',
				$this->getMasterFilter()
			);

			$this->out->addModules( "ext.blueSpiceExtendedSearch.SearchFieldAutocomplete" );
		}
	}

	/**
	 * @return array|null
	 */
	private function getMasterFilter() {
		if (
			!MediaWikiServices::getInstance()->getNamespaceInfo()->
			hasSubpages( $this->out->getTitle()->getNamespace() )
		) {
			// Disable if NS does not support subpages
			return null;
		}

		$patterns = $this->getConfig()->get( 'ESSubpageMasterFilterPatterns' );
		if ( !$patterns ) {
			return null;
		}

		$match = false;
		foreach ( $patterns as $pattern ) {
			$regex = "/^" . $pattern . "/";
			if (
				!preg_match( $regex, $this->out->getTitle()->getPrefixedDBkey() ) &&
				!preg_match( $regex, $this->out->getTitle()->getPrefixedText() )
			) {
				continue;
			}
			$match = true;
			break;
		}

		if ( !$match ) {
			return null;
		}

		$title = $this->out->getTitle()->getDBkey();
		if ( $this->getConfig()->get( 'ESSubpageMasterFilterUseRootOnly' ) ) {
			$titleBits = explode( '/', $this->out->getTitle()->getDBkey() );
			$text = array_shift( $titleBits );
			$newTitle = Title::makeTitle( $this->out->getTitle()->getNamespace(), $text );
			$title = $newTitle->getDBkey();
		}

		$namespace = $this->out->getTitle()->getNamespace();
		if ( $namespace === NS_MAIN ) {
			$namespaceText = Message::newFromKey( 'bs-ns_main' )->text();
		} else {
			$namespaceText = MediaWikiServices::getInstance()->getNamespaceInfo()
				->getCanonicalName( $namespace );
		}

		return [
			'title' => $title,
			'namespace' => [
				'id' => $this->out->getTitle()->getNamespace(),
				'text' => $namespaceText,
			],
		];
	}
}
