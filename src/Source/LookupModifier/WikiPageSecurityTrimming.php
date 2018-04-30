<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageSecurityTrimming extends Base {

	protected $namespaceIdBlacklist = [];

	/**
	 * We can not use a namespace whitelist here and just add a filter,
	 * because all documents that to not have the 'namespace' field (like
	 * e.g. ExternalFile) will also filtered!
	 * Instead we make use of the 'must_not' clause in boolean query. This
	 * clause gets applied on the already filtered result.
	 *
	 * As the Lookup object does not provide a method for this (by design),
	 * we manipulate it through its nature of being an ArrayObject.
	 *
	 * This can be seen as a reference implementation, not as
	 * "hacky workaround"
	 *
	 * See, https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
	 */
	public function apply() {
		$aNamespaceIds = $this->oContext->getLanguage()->getNamespaceIds();
		$this->namespaceIdBlacklist = [];

		foreach( $aNamespaceIds as $sNsText => $iNsId ) {
			if( $this->userCanNotRead( $iNsId ) ) {
				$this->namespaceIdBlacklist[] = $iNsId;
			}
		}

		if( !empty( $this->namespaceIdBlacklist ) ) {
			$this->oLookup->addBoolMustNotTerms( 'namespace', $this->namespaceIdBlacklist );
		}
	}

	protected function userCanNotRead( $iNsId ) {
		$oTitle = \Title::makeTitle( $iNsId, 'Dummy');
		return !$oTitle->userCan( 'read' );
	}

	public function undo() {
	}
}