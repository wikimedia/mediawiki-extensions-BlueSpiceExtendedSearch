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

	const AC_RANK_NORMAL = 'normal';
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

		//Experimental
		$user = $this->getContext()->getUser();
		if( $user->isLoggedIn() ) {
			$resultRelevance = new \BS\ExtendedSearch\ResultRelevance( $user, $resultObject->getId() );
			$result['user_relevance'] = $resultRelevance->getValue();
		} else {
			$result['user_relevance'] = 0;
		}
		//End Experimental

		$type = $result['type'];
		$result['typetext'] = $this->getTypeText( $type );

		if( $this->isFeatured( $result ) ) {
			$result['featured'] = 1;
		}

		if( !isset( $originalValues['ctime'] ) || !isset( $originalValues['mtime'] ) ) {
			// Not all types have these
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
			if( !isset( $result['mtime'] ) || $result['rank'] !== 'top' ) {
				continue;
			}

			$result['modified_time'] = $this->getContext()->getLanguage()->timeanddate( $result['mtime'] );
			unset( $result['mtime'] );
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
	 * Exact matches are TOP, matches containing search term are NORMAL,
	 * and matches not containing search term (fuzzy) are SECONDARY
	 *
	 * Ranking controls where result will be shown( which part of AC popup )
	 *
	 * @param type $results
	 * @param type $searchData
	 */
	public function rankAutocompleteResults( &$results, $searchData ) {
		$top = $this->getACHighestScored( $results );
		foreach( $results as &$result ) {
			if( $result['is_ranked'] == true ) {
				return;
			}

			if( strtolower( $result['basename'] ) == strtolower( $searchData['value'] ) && $top['_id'] === $result['_id'] ) {
				$result['rank'] = self::AC_RANK_TOP;
			} else if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) !== false ) {
				$result['rank'] = self::AC_RANK_NORMAL;
			} else {
				$result['rank'] = self::AC_RANK_SECONDARY;
			}

			$result['is_ranked'] = true;
		}
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

	/**
	 * Allows sources to modify filterCfg if needed
	 *
	 * @param array $aggs
	 * @param array $filterCfg
	 * @param bool $fieldsWithANDEnabled
	 */
	public function formatFilters( &$aggs, &$filterCfg, $fieldsWithANDEnabled = false ) {
		return;
	}

	protected function getACHighestScored( $results ) {
		$highest = false;
		foreach( $results as $result ) {
			if( !$highest || ( $result['score'] > $highest['score'] ) ) {
				$highest = $result;
			}
		}

		return $highest;
	}
}
