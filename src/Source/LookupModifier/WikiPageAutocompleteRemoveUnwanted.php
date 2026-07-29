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
		// Namespace-based query trimming is global for all types and would hide repofile.
		// NS_FILE wikipages are filtered later in wikipage formatter instead.
		$toExclude = array_diff( $toExclude, [ NS_FILE ] );
		$toExclude = array_values( $toExclude );

		$this->lookup->addBoolMustNotTerms( 'namespace', $toExclude );
	}

}
