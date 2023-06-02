<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

class WikiPageBoosters extends LookupModifier {

	public function apply() {
		// Boost "wikipage" type as its most important on a wiki
		$this->lookup->boostSourceType( 'wikipage', 5 );
		// Boost NS_MAIN
		$this->lookup->addShouldTerms( 'namespace', NS_MAIN, 2, false );
		// Boost $wgContentNamespaces
		$contentNamespaces = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getContentNamespaces();
		$this->lookup->addShouldTerms( 'namespace', array_values( $contentNamespaces ), 4, false );
		// Boost subject namespaces (non-talk, non-specialpage)
		$subjectNamespaces = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getSubjectNamespaces();
		$this->lookup->addShouldTerms( 'namespace', array_values( $subjectNamespaces ), 3, false );
	}

	public function undo() {
		$this->lookup->removeSourceTypeBoost( 'wikipage' );
		$this->lookup->removeShouldTerms( 'namespace' );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [
			Backend::QUERY_TYPE_AUTOCOMPLETE,
			Backend::QUERY_TYPE_SEARCH
		];
	}
}
