<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

class WikiPageAutocompleteRemoveUnwanted extends WikiPageRemoveUnwanted {

	protected const SEARCH_TYPE = Backend::QUERY_TYPE_AUTOCOMPLETE;

	public function apply() {
		// Do not search in talk namespaces
		$talkNamespaces = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getTalkNamespaces();
		$excludedByConfig = $this->getNamespacesExcludedByConfig();
		$toExclude = array_merge( $talkNamespaces, $excludedByConfig );
		$toExclude = array_unique( $toExclude );

		$this->lookup->addBoolMustNotTerms( 'namespace', $toExclude );
	}

}
