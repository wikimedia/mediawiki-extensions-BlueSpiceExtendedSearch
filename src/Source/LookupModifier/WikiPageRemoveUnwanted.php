<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

class WikiPageRemoveUnwanted extends LookupModifier {

	protected const SEARCH_TYPE = Backend::QUERY_TYPE_SEARCH;

	public function apply() {
		if ( $this->getNamespacesExcludedByConfig() ) {
			$this->lookup->addBoolMustNotTerms( 'namespace', $this->getNamespacesExcludedByConfig() );
		}
	}

	public function undo() {
		$this->lookup->removeBoolMustNot( 'namespace' );
	}

	/**
	 * @return array
	 */
	public function getNamespacesExcludedByConfig(): array {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$excludedNamespaces = $config->get( 'ESExcludeNamespaces' );
		$toExclude = $excludedNamespaces[ static::SEARCH_TYPE ] ?? [];
		return array_unique( $toExclude );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ static::SEARCH_TYPE ];
	}
}
