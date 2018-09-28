<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageLanguageAggregation extends Base {

	public function apply() {
		$this->oLookup->setBucketTermsAggregation( 'page_language' );
	}

	public function undo() {
		$this->oLookup->removeBucketTermsAggregation( 'page_language' );
	}
}
