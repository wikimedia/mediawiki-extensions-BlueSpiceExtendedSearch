<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageQSSourceFields extends Base {

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		$queryString = $this->oLookup->getQueryString();

		$fields = [ 'rendered_content', 'prefixed_title', 'display_title^2' ];
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
			$queryString['fields'] = array_diff( $queryString['fields'], [ 'rendered_content', 'prefixed_title', 'display_title^2' ] );
		}

		$this->oLookup->setQueryString( $queryString );
	}

}
