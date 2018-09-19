<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageBoosters extends Base {

	public function apply() {
		// Boost "wikipage" type as its most important on a wiki
		$this->oLookup->addShouldMatch( '_type', 'wikipage', 5 );
		// Boost NS_MAIN
		$this->oLookup->addShouldTerms( 'namespace', NS_MAIN, 2, false );
		// Boost $wgContentNamespaces
		$contentNamespaces = \MWNamespace::getContentNamespaces();
		$this->oLookup->addShouldTerms( 'namespace', array_values( $contentNamespaces ), 4, false );
		// Boost subject namespaces (non-talk, non-specialpage)
		$subjectNamespaces = \MWNamespace::getSubjectNamespaces();
		$this->oLookup->addShouldTerms( 'namespace', array_values( $subjectNamespaces ), 3, false );
	}

	public function undo() {
		$this->oLookup->removeShouldMatch( '_type' );
		$this->oLookup->removeShouldTerms( 'namespace' );
	}

}
