<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\MediaWikiServices;

class WikiPageAutocompleteRemoveUnwanted extends Base {

	public function apply() {
		// Do not search in talk namespaces
		$talkNamespaces = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getTalkNamespaces();
		$this->oLookup->addBoolMustNotTerms( 'namespace', array_values( $talkNamespaces ) );
	}

	public function undo() {
		$this->oLookup->removeBoolMustNot( 'namespace' );
	}

}
