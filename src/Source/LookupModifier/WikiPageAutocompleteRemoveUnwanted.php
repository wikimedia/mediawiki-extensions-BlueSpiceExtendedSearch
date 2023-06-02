<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\MediaWikiServices;

class WikiPageAutocompleteRemoveUnwanted extends LookupModifier {

	public function apply() {
		// Do not search in talk namespaces
		$talkNamespaces = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getTalkNamespaces();
		$this->lookup->addBoolMustNotTerms( 'namespace', array_values( $talkNamespaces ) );
	}

	public function undo() {
		$this->lookup->removeBoolMustNot( 'namespace' );
	}

}
