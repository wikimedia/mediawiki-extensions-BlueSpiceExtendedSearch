<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageQSSourceFields extends Base {

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		$queryString = $this->oLookup->getQueryString();
		//Search in basename field and boost it by 3
		$fields = ['rendered_content'];
		if( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_merge( $queryString['fields'], $fields );
		} else {
			$queryString['fields'] = $fields;
		}

		$this->oLookup->setQueryString( $queryString );
	}

	public function undo() {
		$queryString = $this->oLookup->getQueryString();

		if( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_diff( $queryString['fields'], ['rendered_content'] );
		}

		$this->oLookup->setQueryString( $queryString );
	}

}
