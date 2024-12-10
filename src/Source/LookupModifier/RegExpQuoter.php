<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Lookup;
use MediaWiki\MediaWikiServices;

class RegExpQuoter extends LookupModifier {

	/**
	 * @var array
	 */
	protected $queryString;

	/**
	 * @var string
	 */
	protected $originalQuery;

	/**
	 *
	 * @return void
	 */
	public function apply() {
		$this->queryString = $this->lookup->getQueryString();
		$this->originalQuery = $this->queryString['query'];

		/*
		 * If search query matches one of the date patterns:
		 * we will quote it.
		 * */
		$patterns = $this->getPatterns();
		$this->queryString['query'] = $this->quoteQueryByPattern( $this->queryString['query'], $patterns );
		$this->lookup->setQueryString( $this->queryString );
	}

	/**
	 *
	 * @return void
	 */
	public function undo() {
		$this->queryString = $this->lookup->getQueryString();
		$this->queryString['query'] = $this->originalQuery;
		$this->lookup->setQueryString( $this->queryString );
	}

	/**
	 *
	 * @return Lookup
	 */
	public function getLookup() {
		return $this->lookup;
	}

	/**
	 *
	 * @return int
	 */
	public function getPriority() {
		return 51;
	}

	/**
	 *
	 * @return array
	 */
	protected function getPatterns() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		if ( $config->has( 'ESLookupModifierRegExPatterns' ) ) {
			return $config->get( 'ESLookupModifierRegExPatterns' );
		}
		return [];
	}

	/**
	 * @param string $query
	 * @param array $pcrePatterns
	 * @return string
	 */
	protected function quoteQueryByPattern( $query, $pcrePatterns ) {
		$replaceCounter = 0;
		$mapping = [];

		$query = preg_replace_callback(
			'#"(.*?)"#',
			static function ( $matches ) use ( &$replaceCounter, &$mapping ) {
				$placeholder = "###Q$replaceCounter###";
				$replaceCounter++;
				$mapping[$placeholder] = $matches[1];
				return $placeholder;
			},
			$query
		);

		foreach ( $pcrePatterns as $singlePattern ) {

			if ( preg_match_all( "/$singlePattern/", $query, $m1 ) ) {

				if ( !count( $m1 ) ) {
					continue;
				}
				$m2 = $m1[0];
				$m2 = array_unique( $m2 );

				foreach ( $m2 as $k => $matchedPart ) {
					$replaceCounter++;
					$placeholder = "###Q$replaceCounter###";
					$mapping[$placeholder] = $matchedPart;
					$query = str_replace( $matchedPart, $placeholder, $query );
				}

			}
		}
		foreach ( $mapping as $k => $v ) {
			$query = str_replace( $k, '"' . $v . '"', $query );
		}

		return $query;
	}

}
