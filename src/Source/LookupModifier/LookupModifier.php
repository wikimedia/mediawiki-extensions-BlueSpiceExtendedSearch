<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Plugin\ILookupModifier;
use MediaWiki\Context\IContextSource;

abstract class LookupModifier implements ILookupModifier {

	/**
	 *
	 * @var Lookup
	 */
	protected $lookup = null;

	/**
	 *
	 * @var IContextSource
	 */
	protected $context = null;

	/**
	 *
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 */
	public function __construct( $lookup, $context ) {
		$this->lookup = $lookup;
		$this->context = $context;
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
