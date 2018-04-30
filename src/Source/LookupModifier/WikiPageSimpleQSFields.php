<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageSimpleQSFields extends Base {

	public function apply() {
		$simpleQS = $this->oLookup->getSimpleQueryString();

		$fields = ['source_content', 'rendered_content'];
		if( isset( $simpleQS['fields'] ) && is_array( $simpleQS['fields'] ) ) {
			$simpleQS['fields'] = array_merge( $simpleQS['fields'], $fields );
		} else {
			$simpleQS['fields'] = $fields;
		}

		$this->oLookup->setSimpleQueryString( $simpleQS );
	}

	public function undo() {
		$simpleQS = $this->oLookup->getSimpleQueryString();

		if( isset( $simpleQS['fields'] ) && is_array( $simpleQS['fields'] ) ) {
			$simpleQS['fields'] = array_diff( $simpleQS['fields'], ['source_content', 'rendered_content'] );
		}

		$this->oLookup->setSimpleQueryString( $simpleQS );
	}
}
