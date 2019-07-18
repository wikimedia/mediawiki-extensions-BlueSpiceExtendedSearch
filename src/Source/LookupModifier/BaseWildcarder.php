<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Wildcarder;

class BaseWildcarder extends Base {
	/**
	 * @var array
	 */
	protected $queryString;
	/**
	 * @var string
	 */
	protected $originalQuery;

	public function apply() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->originalQuery = $this->queryString['query'];

		$wildcarder = Wildcarder::factory( trim( $this->originalQuery ) );
		$this->setWildcarded( $wildcarder->getWildcarded() );
	}

	protected function setWildcarded( $wildcarded ) {
		$this->queryString['query'] = $wildcarded;
		$this->queryString['default_operator'] = 'OR';
		$this->oLookup->setQueryString( $this->queryString );
	}

	public function undo() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->queryString['query'] = $this->originalQuery;
		$this->oLookup->setQueryString( $this->queryString );
	}

	public function getPriority() {
		return 90;
	}
}
