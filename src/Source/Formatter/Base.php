<?php

namespace BS\ExtendedSearch\Source\Formatter;

class Base {
	/**
	 * Used to separate multiple values in arrays
	 * when they are displayed in the UI
	 */
	const VALUE_SEPARATOR = ', ';

	/**
	 * Used to indicate there are more valus than
	 * can be displayed
	 */
	const MORE_VALUES_TEXT = '...';

	const AC_RANK_PRIMARY = 'primary';
	const AC_RANK_SECONDARY = 'secondary';
	const AC_RANK_TOP = 'top';

	/**
	 *
	 * @var \BS\ExtendedSearch\Source\Base
	 */
	protected $source;

	/**
	 *
	 * @var \BS\ExtendedSearch\Lookup
	 */
	protected $lookup;

	/**
	 *
	 * @param \BS\ExtendedSearch\Source\Base $source
	 */
	public function __construct( $source ) {
		$this->source = $source;
		//Just for convinience, as many of the formatters would use it
		$this->linkRenderer = $this->source->getBackend()->getService( 'LinkRenderer' );
	}

	/**
	 * Sets current instance of Lookup object that the
	 * result being formatted
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 */
	public function setLookup( $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * Convenience function - returns RequestContext object
	 *
	 * @return \RequestContext
	 */
	public function getContext() {
		return $this->source->getBackend()->getContext();
	}

	/**
	 * Returns structure of the result for each source
	 * It allows sources to modify default result structure
	 *
	 * @param array $defaultResultStructure
	 * @returns array
	 */
	public function getResultStructure( $defaultResultStructure = [] ) {
		return $defaultResultStructure;
	}

	/**
	 * Allows sources to modify data returned by ES,
	 * before it goes to the client-side
	 *
	 * @param array $result
	 * @param \Elastica\Result $resultObject
	 */
	public function format( &$result, $resultObject ) {
		//Base class format must work with original values
		//because it might be called multiple times
		$originalValues = $resultObject->getData();
		$result['type'] = $resultObject->getType();
		$result['score'] = $resultObject->getScore();

		$type = $result['type'];
		$result['typetext'] = $this->getTypeText( $type );

		if( $this->isFeatured( $result ) ) {
			$result['featured'] = 1;
		}

		if( !isset( $originalValues['ctime'] ) || !isset( $originalValues['mtime'] ) ) {
			//If those are not set for the given type
			return;
		}
		$result['ctime'] = $this->getContext()->getLanguage()->date( $originalValues['ctime'] );
		$result['mtime'] = $this->getContext()->getLanguage()->date( $originalValues['mtime'] );
	}

	/**
	 * Allows sources to modify results of autocomplete query
	 *
	 * @param array $results
	 * @param array $searchData
	 */
	public function formatAutocompleteResults( &$results, $searchData ) {
		foreach( $results as &$result ) {
			$type = $result['type'];
			$result['typetext'] = $this->getTypeText( $type );
		}
	}

	protected function getTypeText( $type ) {
		$typeText = $type;
		if(  wfMessage( "bs-extendedsearch-source-type-$type-label" )->exists() ) {
			$typeText =  wfMessage( "bs-extendedsearch-source-type-$type-label" )->plain();
		}

		return $typeText;
	}

	/**
	 * Allows sources to change ranking of the autocomplete query results
	 * Exact matches are TOP, matches containing search term are PRIMARY,
	 * and matches not containing search term (fuzzy) are SECONDARY
	 *
	 * Ranking controls where result will be shown( which part of AC popup )
	 *
	 * @param type $results
	 * @param type $searchData
	 */
	public function rankAutocompleteResults( &$results, $searchData ) {
		foreach( $results as &$result ) {
			if( $result['is_ranked'] == true ) {
				return;
			}

			if( strtolower( $result['basename'] ) == strtolower( $searchData['value'] ) ) {
				$result['rank'] = self::AC_RANK_TOP;
			} else if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) !== false ) {
				$result['rank'] = self::AC_RANK_PRIMARY;
			} else {
				$result['rank'] = self::AC_RANK_SECONDARY;
			}

			$result['is_ranked'] = true;
		}
	}

	/**
	 * Allows modifying scoring of the AC results, after query has ran.
	 *
	 * @param array $results
	 * @param array $searchData
	 */
	public function scoreAutocompleteResults( &$results, $searchData ) {
		foreach( $results as &$result ) {
			$result['score'] += $this->getMatchPercentage( $result['basename'], $searchData['value'] );
		}
	}
	protected function getMatchPercentage( $result, $term ) {
		$matches = [];
		//How many times search term is repeated
		preg_match_all( '/' . $term . '/', $result, $matches );

		$termLength = strlen( $term ) * count( $matches );

		return ( $termLength * 100 ) / strlen( $result );
	}

	/**
	 * Basic implementation. Checks if searhed term
	 * matches result exactly
	 *
	 * @param array $result
	 * @return boolean
	 */
	protected function isFeatured( $result ) {
		if( $this->lookup == null ) {
			return false;
		}

		$queryString = $this->lookup->getQueryString();
		if( isset( $queryString['query'] ) == false ) {
			return false;
		}

		$term = $queryString['query'];
		if( strtolower( $term ) == strtolower( $result['basename'] ) ) {
			return true;
		}
	}
}