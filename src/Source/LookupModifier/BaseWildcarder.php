<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

/**
 * This class wildcards single words, without operators.
 * Logic is that users when typing a single word, want to see
 * results before they finish typing the whole word
 */
class BaseWildcarder extends Base {
	/**
	 * @var array
	 */
	protected $operators = ['+', '|', '-', "\"", "*", "(", ")", "~"];
	/**
	 * @var array
	 */
	protected $queryString;
	/**
	 * @var string
	 */
	protected $originalQuery;
	/**
	 * @var string
	 */
	protected $wildcarded;

	public function apply() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->originalQuery = $this->queryString['query'];
		$this->wildcarded = trim( $this->originalQuery );

		if ( $this->containsOperators() ) {

			return;
		}

		$this->escapeColons();
		if( $this->isSinglePlainWord() ) {
			$this->wildcardTerm();
		}
		$this->setWildcarded();
	}

	/**
	 * Returns true if search term has no spaces,
	 * and no operators - meaning it should be wildcarded
	 *
	 * @return boolean
	 */
	protected function isSinglePlainWord() {
		if( strlen( $this->wildcarded ) == 0 ) {
			return false;
		}

		if( strpos( $this->wildcarded, ' ' ) === false ) {
			return true;
		}
		return false;
	}

	protected function containsOperators() {
		$pattern = [];
		foreach( $this->operators as $op ) {
			$pattern[] = "\\$op";
		}
		$pattern = "/" . implode( '|', $pattern ) . "/";

		if( preg_match( $pattern, $this->originalQuery ) ) {
			return true;
		}
		return false;
	}

	protected function wildcardTerm() {
		$this->wildcarded = "*{$this->wildcarded} OR {$this->wildcarded}*";
	}

	protected function setWildcarded() {
		$this->queryString['query'] = $this->wildcarded;
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

	protected function escapeColons() {
		$this->wildcarded = str_replace( ':', '\\:', $this->wildcarded );
	}
}