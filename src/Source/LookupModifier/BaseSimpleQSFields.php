<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseSimpleQSFields extends LookupModifier {

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		$this->lookup->addSourceField( 'basename' );
		$this->lookup->addSourceField( 'congregated' );
		$this->lookup->addSourceField( 'ctime' );
		$this->lookup->addSourceField( 'mtime' );

		$simpleQS = $this->lookup->getQueryString();
		$fields = [ 'basename^4', 'congregated' ];
		if ( isset( $simpleQS['fields'] ) && is_array( $simpleQS['fields'] ) ) {
			$simpleQS['fields'] = array_merge( $simpleQS['fields'], $fields );
		} else {
			$simpleQS['fields'] = $fields;
		}

		$this->lookup->setQueryString( $simpleQS );
	}

	public function undo() {
		$simpleQS = $this->lookup->getQueryString();

		if ( isset( $simpleQS['fields'] ) && is_array( $simpleQS['fields'] ) ) {
			$simpleQS['fields'] = array_diff( $simpleQS['fields'], [ 'basename^4', 'congregated' ] );
		}

		$this->lookup->setQueryString( $simpleQS );

		$this->lookup->removeSourceField( 'basename' );
		$this->lookup->removeSourceField( 'congregated' );
		$this->lookup->removeSourceField( 'ctime' );
		$this->lookup->removeSourceField( 'mtime' );
	}

}
