<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseTagsAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'tags' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'tags' );
	}
}
