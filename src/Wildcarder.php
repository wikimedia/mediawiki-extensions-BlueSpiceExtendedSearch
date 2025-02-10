<?php

namespace BS\ExtendedSearch;

use MediaWiki\Config\ConfigException;
use MediaWiki\MediaWikiServices;

class Wildcarder {

	/**
	 * @var array
	 */
	protected $wildcardingOperators;

	/**
	 * @var array
	 */
	protected $wildcardingSeparators;

	/**
	 * @var string
	 */
	protected $original;

	/**
	 * @var string
	 */
	protected $wildcarded;

	/**
	 * @var bool
	 */
	protected $done = false;

	/**
	 * @param string $original
	 * @return Wildcarder
	 * @throws ConfigException
	 */
	public static function factory( $original ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return new static(
			$original,
			$config->get( 'ESWildcardingOperators' ),
			$config->get( 'ESWildcardingSeparators' )
		);
	}

	/**
	 *
	 * @param string $original
	 * @param array $wildcardingOperators
	 * @param array $wildcardingSeparators
	 */
	protected function __construct( $original, $wildcardingOperators, $wildcardingSeparators ) {
		$this->original = $original;
		$this->wildcardingOperators = $wildcardingOperators;
		$this->wildcardingSeparators = $wildcardingSeparators;
	}

	/**
	 * Get the wildcarded string
	 *
	 * @param bool $redo
	 * @return string
	 */
	public function getWildcarded( $redo = false ) {
		if ( !$this->done || $redo ) {
			$this->process();
		}

		return $this->wildcarded;
	}

	/**
	 * Get term split on separators
	 *
	 * @param array $additionalSeparators
	 * @return array
	 */
	public function getSeparated( $additionalSeparators = [] ) {
		$separators = array_merge( $additionalSeparators, $this->wildcardingSeparators );
		$regex = implode( '|\\', $separators );
		$tokens = preg_split( "/(\s|$regex)/", $this->original );
		$separated = [];
		foreach ( $tokens as $token ) {
			if ( $token === '' ) {
				continue;
			}
			$separated[] = $token;
		}
		return $separated;
	}

	/**
	 * Replace all separators with a given replacement
	 *
	 * @param string $replacement
	 * @param array $additionalSeparators
	 * @return string
	 */
	public function replaceSeparators( $replacement = '', ?array $additionalSeparators = [] ) {
		$term = $this->original;
		$separators = array_merge( $additionalSeparators, $this->wildcardingSeparators );
		foreach ( $separators as $separator ) {
			$term = str_replace( $separator, $replacement, $term );
		}
		return $term;
	}

	/**
	 * Checks if term contains operators
	 *
	 * @return bool
	 */
	public function containsOperators() {
		$pattern = [];
		foreach ( $this->wildcardingOperators  as $op ) {
			$pattern[] = "\\$op";
		}
		$pattern = "/" . implode( '|', $pattern ) . "/";

		if ( preg_match( $pattern, $this->original ) ) {
			return true;
		}

		// Special operators should not be removable by config
		if ( strpos( $this->original, 'AND' ) || strpos( $this->original, 'OR' ) ) {
			return true;
		}
		return false;
	}

	protected function process() {
		$this->wildcarded = trim( $this->original );

		if ( $this->containsOperators() ) {
			return;
		}

		$this->removeUnsupportedChars();
		$this->escape();
		$this->doWildcarding();
		$this->done = true;
	}

	private function removeUnsupportedChars() {
		$this->wildcarded = preg_replace( '/„|“|’/', '', $this->wildcarded );
	}

	protected function doWildcarding() {
		$quoted = $this->getQuotedParts();
		if ( empty( $quoted ) ) {
			$this->wildcarded = $this->doWildcardSnippet( $this->wildcarded );
			return;
		}
		$progress = 0;
		foreach ( $quoted as $quotedSnippet ) {
			$pos = strpos( $this->wildcarded, $quotedSnippet );
			if ( $pos === false ) {
				continue;
			}
			$toWildcard = substr( $this->wildcarded, $progress, $pos - $progress - 1 );
			$wildcarded = $this->doWildcardSnippet( $toWildcard );
			$this->wildcarded = str_replace( $toWildcard, $wildcarded, $this->wildcarded );
			$progress = strpos( $this->wildcarded, $quotedSnippet ) + strlen( $quotedSnippet ) + 1;
		}
	}

	/**
	 * @param string $snippet
	 * @return string
	 */
	private function doWildcardSnippet( $snippet ) {
		foreach ( $this->wildcardingSeparators as $sep ) {
			$snippet = str_replace( $sep, ' ', $snippet );
			$snippet = preg_replace( '/\s+/', ' ', $snippet );
		}
		$terms = explode( ' ', $snippet );
		foreach ( $terms as &$term ) {
			if ( $term == '' ) {
				continue;
			}
			$term = "($term OR *$term OR $term* OR *$term*)";
		}
		return implode( ' ', $terms );
	}

	/**
	 *
	 * @return array
	 */
	protected function getQuotedParts() {
		$quoted = [];
		preg_match_all( '/"([^"]*)"/', $this->wildcarded, $quoted );
		return $quoted[1];
	}

	protected function escape() {
		$this->wildcarded = str_replace( ':', '\\:', $this->wildcarded );
		$this->wildcarded = str_replace( '/', '\\/', $this->wildcarded );
	}
}
