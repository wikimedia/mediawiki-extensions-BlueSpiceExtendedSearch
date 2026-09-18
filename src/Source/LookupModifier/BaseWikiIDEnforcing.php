<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Lookup;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\WikiMap\WikiMap;

class BaseWikiIDEnforcing extends LookupModifier {

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @param Config $searchConfig
	 */
	public function __construct(
		$lookup,
		$context,
		private readonly Config $searchConfig
	) {
		parent::__construct( $lookup, $context );
	}

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		$boostFactor = $this->searchConfig->get( 'ESLocalWikiBoostFactor' ) ?? 5;
		// Boost results of the current wiki
		$this->lookup->addShouldTerms( 'wiki_id', WikiMap::getCurrentWikiId(), $boostFactor );
		$this->lookup->addTermsFilter( 'wiki_id', WikiMap::getCurrentWikiId() );
	}

	public function undo() {
		$this->lookup->removeShouldTerms( 'wiki_id', WikiMap::getCurrentWikiId() );
		$this->lookup->removeTermsFilter( 'wiki_id', WikiMap::getCurrentWikiId() );
	}

}
