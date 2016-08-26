<?php

namespace BS\ExtendedSearch\Source\Updater;

class Base {

	/**
	 *
	 * @var \Elastica\Client
	 */
	protected $oClient = null;

	public function __construct( $oSource ) {
		$this->oClient = $oClient;
	}

	public function init( &$aHooks ) {
		//Just a stub. Needs to be implemented by subclasses
	}
}