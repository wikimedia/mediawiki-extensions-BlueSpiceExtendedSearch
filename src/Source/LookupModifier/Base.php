<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

abstract class Base {

	/**
	 *
	 * @var BS\ExtendedSearch\Lookup
	 */
	protected $oLookup = null;

	/**
	 *
	 * @var \IContextSource
	 */
	protected $oContext = null;

	/**
	 *
	 * @param BS\ExtendedSearch\Lookup $oLookup
	 * @param \IContextSource $oContext
	 */
	public function __construct( &$oLookup, $oContext ) {
		$this->oLookup = $oLookup;
		$this->oContext = $oContext;
	}

	abstract public function apply();
}