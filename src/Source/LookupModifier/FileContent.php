<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class FileContent extends LookupModifier {

	public function apply() {
		$this->lookup->addSourceField( 'filename' );
		$this->lookup->addSourceField( 'extension' );
		$this->lookup->addSourceField( 'mime_type' );
		$this->lookup->addSourceField( 'attachment.content' );
		$this->lookup->addHighlighter( 'attachment.content' );

		// 1. - Add searching in file content field
		$queryString = $this->lookup->getQueryString();
		$fields = [ 'attachment.content', 'extension', 'filename', 'mime_type' ];
		if ( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_merge( $queryString['fields'], $fields );
		} else {
			$queryString['fields'] = $fields;
		}

		$this->lookup->setQueryString( $queryString );

		// 2. - Add highligter in file content field
		$this->lookup->addHighlighter( 'attachment.content' );
	}

	public function undo() {
		$queryString = $this->lookup->getQueryString();

		if ( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_diff( $queryString['fields'], [ 'attachment.content' ] );
		}

		$this->lookup->setQueryString( $queryString );

		$this->lookup->removeHighlighter( 'attachment.content' );

		$this->lookup->removeSourceField( 'filename' );
		$this->lookup->removeSourceField( 'extension' );
		$this->lookup->removeSourceField( 'mime_type' );
		$this->lookup->removeSourceField( 'attachment.content' );
	}

}
