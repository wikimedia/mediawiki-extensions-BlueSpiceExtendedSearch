<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageCategoriesAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'categories' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'categories' );
	}
}
