<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageNamespaceTextAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'namespace_text' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'namespace_text' );
	}
}
