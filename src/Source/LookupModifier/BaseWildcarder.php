<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Wildcarder;
use IContextSource;
use MediaWiki\MediaWikiServices;

class BaseWildcarder extends Base {
	/**
	 * @var array
	 */
	protected $queryString;
	/**
	 * @var string
	 */
	protected $originalQuery;

	/** @var string */
	protected $defaultOperator;

	/**
	 *
	 * @param Lookup $oLookup
	 * @param IContextSource $oContext
	 * @param string $defaultOperator
	 */
	public function __construct( $oLookup, $oContext, $defaultOperator ) {
		parent::__construct( $oLookup, $oContext );
		$this->defaultOperator = $defaultOperator;
	}

	/**
	 * @param MediaWikiServices $services
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return Base
	 */
	public static function factory(
		MediaWikiServices $services, Lookup $lookup, IContextSource $context
	) {
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$defaultOperator = $config->get( 'ESDefaultSearchOperator' );
		return new static( $lookup, $context, $defaultOperator );
	}

	public function apply() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->originalQuery = trim( strip_tags( $this->queryString['query'] ) );

		$wildcarder = Wildcarder::factory( $this->originalQuery );
		$this->setWildcarded( $wildcarder->getWildcarded() );
	}

	/**
	 *
	 * @param string $wildcarded
	 */
	protected function setWildcarded( $wildcarded ) {
		$this->queryString['query'] = $wildcarded;
		$this->queryString['default_operator'] = $this->defaultOperator;
		$this->oLookup->setQueryString( $this->queryString );
	}

	public function undo() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->queryString['query'] = $this->originalQuery;
		$this->oLookup->setQueryString( $this->queryString );
	}

	/**
	 *
	 * @return int
	 */
	public function getPriority() {
		return 90;
	}
}
