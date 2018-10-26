<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

/**
 * This class wildcards single words, without operators.
 * Logic is that users when typing a single word, want to see
 * results before they finish typing the whole word
 */
class BaseWildcarder extends Base {
	protected $operators = ['+', '|', '-', "\"", "*", "(", ")", "~"];
	protected $queryString;
	protected $originalQuery;

	public function apply() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->originalQuery = $this->queryString['query'];

		if( $this->isSinglePlainWord() ) {
			return $this->wildcardTerm();
		}
	}

	/**
	 * Returns true if search term has no spaces,
	 * and no operators - meaning it should be wildcarded
	 *
	 * @return boolean
	 */
	protected function isSinglePlainWord() {
		if( strlen( $this->originalQuery ) == 0 ) {
			return false;
		}

		if( strpos( $this->originalQuery, ' ' ) === false && !$this->containsOperators() ) {
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
		$wildcarded = "*$this->originalQuery OR $this->originalQuery*";
		$this->queryString['query'] = $wildcarded;
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