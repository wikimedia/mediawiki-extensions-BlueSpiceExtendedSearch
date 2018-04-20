<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageNamespaceTextAggregation extends Base {

	public function apply() {
		$this->oLookup->setBucketTermsAggregation( 'namespace_text' );
	}

	public function undo() {
		$this->oLookup->removeBucketTermsAggregation( 'namespace_text' );
	}
}