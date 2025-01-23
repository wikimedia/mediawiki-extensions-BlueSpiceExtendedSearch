<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Wildcarder;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;

class BaseWildcarder extends LookupModifier {
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
	 * @param Lookup $lookup
	 * @param IContextSource $oContext
	 * @param string $defaultOperator
	 */
	public function __construct( $lookup, $oContext, $defaultOperator ) {
		parent::__construct( $lookup, $oContext );
		$this->defaultOperator = $defaultOperator;
	}

	/**
	 * @param MediaWikiServices $services
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return LookupModifier
	 */
	public static function factory(
		MediaWikiServices $services, Lookup $lookup, IContextSource $context
	) {
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$defaultOperator = $config->get( 'ESDefaultSearchOperator' );
		return new static( $lookup, $context, $defaultOperator );
	}

	public function apply() {
		$this->queryString = $this->lookup->getQueryString();
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
		$this->lookup->setQueryString( $this->queryString );
	}

	public function undo() {
		$this->queryString = $this->lookup->getQueryString();
		$this->queryString['query'] = $this->originalQuery;
		$this->lookup->setQueryString( $this->queryString );
	}

	/**
	 *
	 * @return int
	 */
	public function getPriority() {
		return 90;
	}
}
