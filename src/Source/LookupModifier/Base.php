<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

abstract class Base {
	const TYPE_SEARCH = 'search';
	const TYPE_AUTOCOMPLETE = 'autocomplete';

	/**
	 *
	 * @var \BS\ExtendedSearch\Lookup
	 */
	protected $oLookup = null;

	/**
	 *
	 * @var \IContextSource
	 */
	protected $oContext = null;

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup $oLookup
	 * @param \IContextSource $oContext
	 */
	public function __construct( &$oLookup, $oContext ) {
		$this->oLookup = $oLookup;
		$this->oContext = $oContext;
	}

	/**
	 * Gets how far down should the LM be executed
	 *
	 * Allowed values: 1-100
	 *
	 * @return int
	 */
	public function getPriority() {
		return 1;
	}

	abstract public function apply();

	/**
	 * Remove any sensitive Lookup parts previously added
	 * by this modifier, in case they should not be sent to client
	 */
	abstract public function undo();
}