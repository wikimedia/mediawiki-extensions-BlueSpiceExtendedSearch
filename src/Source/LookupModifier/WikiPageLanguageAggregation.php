<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageLanguageAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'page_language' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'page_language' );
	}
}
