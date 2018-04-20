<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageCategoriesAggregation extends Base {

	public function apply() {
		$this->oLookup->setBucketTermsAggregation( 'categories' );
	}

	public function undo() {
		$this->oLookup->removeBucketTermsAggregation( 'categories' );
	}
}
