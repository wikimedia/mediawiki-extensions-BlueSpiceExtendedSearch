<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseSimpleQSFields extends Base {

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		$simpleQS = $this->oLookup->getSimpleQueryString();
		//Search in basename field and boost it by 3
		$fields = ['basename^3'];
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
			$simpleQS['fields'] = array_diff( $simpleQS['fields'], ['basename^3'] );
		}

		$this->oLookup->setSimpleQueryString( $simpleQS );
	}

}
