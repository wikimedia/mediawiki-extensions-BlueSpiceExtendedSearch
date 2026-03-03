<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\WikiMap\WikiMap;

class BaseWikiIDEnforcing extends LookupModifier {

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		// Boost results of the current wiki
		$this->lookup->addShouldTerms( 'wiki_id', WikiMap::getCurrentWikiId(), 2 );
		$this->lookup->addTermsFilter( 'wiki_id', WikiMap::getCurrentWikiId() );
	}

	public function undo() {
		$this->lookup->removeShouldTerms( 'wiki_id', WikiMap::getCurrentWikiId() );
		$this->lookup->removeTermsFilter( 'wiki_id', WikiMap::getCurrentWikiId() );
	}

}
