<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseDocumentTypeAggregation extends LookupModifier {

	public function apply() {
		$this->lookup->setBucketTermsAggregation( 'document_type' );
	}

	public function undo() {
		$this->lookup->removeBucketTermsAggregation( 'document_type' );
	}
}
