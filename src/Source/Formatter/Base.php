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

	/**
	 *
	 * @var \BS\ExtendedSearch\Source\Base
	 */
	protected $source;

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
	}

	/**
	 * Allows sources to change scoring of the autocomplete query results
	 *
	 * @param type $results
	 * @param type $searchData
	 */
	public function scoreAutocompleteResults( &$results, $searchData ) {
		foreach( $results as &$result ) {
			if( $result['is_scored'] ) {
				continue;
			}

			if( strtolower( $result['basename'] ) == strtolower( $searchData['value'] ) ) {
				$result['score'] = 8;
			} else if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) !== false ) {
				if( strpos( strtolower( $result['basename'] ), strtolower( $searchData['value'] ) ) === 0 ) {
					$result['score'] = 7;
				} else {
					$result['score'] = 6;
				}
			} else {
				$result['score'] = 2;
			}
		}
	}
}