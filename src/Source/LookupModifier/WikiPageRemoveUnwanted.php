<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageRemoveUnwanted extends Base {

	public function apply() {
		// Do not search redirect pages
		$this->oLookup->addBoolMustNotTerms( 'is_redirect', true );
	}

	public function undo() {
		$this->oLookup->removeBoolMustNot( 'is_redirect' );
	}

}
