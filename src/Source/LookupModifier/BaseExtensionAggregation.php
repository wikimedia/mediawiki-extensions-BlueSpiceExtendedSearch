<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseExtensionAggregation extends Base {

	public function apply() {
		$this->oLookup->setBucketTermsAggregation( 'extension' );
	}
}

