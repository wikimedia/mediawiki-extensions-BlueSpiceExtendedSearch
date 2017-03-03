<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageNamespaceTextAggregation extends Base {

	public function apply() {
		$this->oLookup->setBucketTermsAggregation( 'namespace_text' );
	}
}