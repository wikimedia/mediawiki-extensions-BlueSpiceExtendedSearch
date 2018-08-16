<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageAutocompleteRemoveTalk extends Base {

	public function apply() {
		// Do not search in talk namespaces
		$talkNamespaces = \MWNamespace::getTalkNamespaces();
		$this->oLookup->addBoolMustNotTerms( 'namespace', array_values( $talkNamespaces ) );
	}

	public function undo() {
	}

}
