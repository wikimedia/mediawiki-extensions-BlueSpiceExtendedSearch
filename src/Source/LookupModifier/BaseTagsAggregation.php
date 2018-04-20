<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseTagsAggregation extends Base {

	public function apply() {
		$this->oLookup->setBucketTermsAggregation( 'tags' );
	}

	public function undo() {
		$this->oLookup->removeBucketTermsAggregation( 'tags' );
	}
}