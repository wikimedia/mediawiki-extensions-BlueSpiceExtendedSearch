<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ILookupModifier;
use BS\ExtendedSearch\Lookup;
use IContextSource;
use MediaWiki\MediaWikiServices;

abstract class Base implements ILookupModifier {

	/**
	 *
	 * @var Lookup
	 */
	protected $oLookup = null;

	/**
	 *
	 * @var IContextSource
	 */
	protected $oContext = null;

	/**
	 *
	 * @param Lookup $oLookup
	 * @param IContextSource $oContext
	 */
	public function __construct( $oLookup, $oContext ) {
		$this->oLookup = $oLookup;
		$this->oContext = $oContext;
	}

	/**
	 * @param MediaWikiServices $services
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return Base
	 */
	public static function factory( MediaWikiServices $services, Lookup $lookup,
		IContextSource $context ) {
		return new static( $lookup, $context );
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

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_SEARCH ];
	}
}
