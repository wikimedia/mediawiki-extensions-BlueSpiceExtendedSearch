<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class FileContent extends Base {

	public function apply() {
		// 1. - Add searching in file content field
		$queryString = $this->oLookup->getQueryString();
		$fields = [ 'attachment.content' ];
		if( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_merge( $queryString['fields'], $fields );
		} else {
			$queryString['fields'] = $fields;
		}

		$this->oLookup->setQueryString( $queryString );

		// 2. - Add highligter in file content field
		$this->oLookup->addHighlighter( 'attachment.content' );
	}

	public function undo() {
		$queryString = $this->oLookup->getQueryString();

		if( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_diff( $queryString['fields'], ['attachment.content'] );
		}

		$this->oLookup->setQueryString( $queryString );

		$this->oLookup->removeHighlighter( 'attachment.content' );
	}

}