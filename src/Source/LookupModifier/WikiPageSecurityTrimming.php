<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\Title\Title;

class WikiPageSecurityTrimming extends LookupModifier {

	/** @var int[] */
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
		$aNamespaceIds = $this->context->getLanguage()->getNamespaceIds();
		$this->namespaceIdBlacklist = [];

		foreach ( $aNamespaceIds as $sNsText => $iNsId ) {
			if ( $this->userCanNotRead( $iNsId ) ) {
				$this->namespaceIdBlacklist[] = $iNsId;
			}
		}

		if ( !empty( $this->namespaceIdBlacklist ) ) {
			$this->lookup->addBoolMustNotTerms( 'namespace', $this->namespaceIdBlacklist );
		}
	}

	/**
	 *
	 * @param int $iNsId
	 * @return bool
	 */
	protected function userCanNotRead( $iNsId ) {
		$oTitle = Title::makeTitle( $iNsId, 'Dummy' );
		return !\MediaWiki\MediaWikiServices::getInstance()
			->getPermissionManager()
			->userCan( 'read', $this->context->getUser(), $oTitle );
	}

	public function undo() {
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
