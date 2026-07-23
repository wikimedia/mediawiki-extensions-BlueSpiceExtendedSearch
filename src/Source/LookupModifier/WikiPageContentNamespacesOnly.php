<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

/**
 * Limits plain wiki pages ("Page" results) to content namespaces.
 *
 * Only documents of the "wikitext" type are affected, all other document types
 * (blog posts, processes, spreadsheets, user profiles, modules, ...) live in
 * namespaces of their own and must stay searchable.
 */
class WikiPageContentNamespacesOnly extends LookupModifier {

	public function apply() {
		$clause = $this->getClause();
		if ( !$clause ) {
			return;
		}
		$this->lookup->addBoolMustNotQuery( $clause );
	}

	public function undo() {
		$clause = $this->getClause();
		if ( !$clause ) {
			return;
		}
		$this->lookup->removeBoolMustNotQuery( $clause );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_SEARCH, Backend::QUERY_TYPE_AUTOCOMPLETE ];
	}

	/**
	 * Clause matching all wikitext pages outside of the content namespaces
	 *
	 * @return array Empty if the restriction is disabled
	 */
	private function getClause(): array {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		if ( !$config->get( 'ESLimitPagesToContentNamespaces' ) ) {
			return [];
		}
		$contentNamespaces = $services->getNamespaceInfo()->getContentNamespaces();

		return [
			'bool' => [
				'must' => [
					[ 'term' => [ 'document_type' => 'wikitext' ] ]
				],
				'must_not' => [
					[ 'terms' => [ 'namespace' => array_values( $contentNamespaces ) ] ]
				]
			]
		];
	}
}
