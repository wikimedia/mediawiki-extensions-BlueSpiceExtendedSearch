<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseExtensionAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'extension' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'extension' );
	}
}
