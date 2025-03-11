<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageNamespaceTextAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'namespace' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'namespace' );
	}
}
