<?php

namespace BS\ExtendedSearch;

class LookUp extends \ArrayObject {

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	/**
	 *
	 * @param array $aConfig
	 */
	public function __construct( $aConfig = [] ) {
		if( is_array( $aConfig ) ) {
			foreach( $aConfig as $sKey => $mValue ) {
				$this[$sKey] = $mValue;
			}
		}
	}

	protected function ensurePropertyPath( $sPath, $mDefault, &$aBase = null ) {
		$aPathParts = explode( '.', $sPath );

		$current = $this;
		foreach( $aPathParts as $sPathPart ) {
			if( !isset( $current[$sPathPart] ) ) {
				$current[$sPathPart] = array();
			}
			$current = &$current[$sPathPart];
		}

		if( empty( $current )  ) {
			$current = $mDefault;
		}
	}

	/**
	 *
	 * @return array
	 */
	public function getQueryDSL() {
		return (array)$this;
	}

	/**
	 *
	 * @param string $sType
	 * @return Lookup
	 */
	public function setType( $sType ) {
		$this->ensurePropertyPath( 'query.type.value', '' );
		$this['query']['type']['value'] = $sType;
		return $this;
	}

	/**
	 *
	 * @return Lookup
	 */
	public function clearType() {
		$this->ensurePropertyPath( 'query.type.value', '' );
		unset( $this['query']['type'] );
		return $this;
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-simple-query-string-query.html
	 * @param string|array $mValue
	 * @return LookUp
	 */
	public function setSimpleQueryString( $mValue ) {
		$this->ensurePropertyPath( 'query.simple_query_string', [] );
			if( is_array( $mValue ) ) {
				$this['query']['simple_query_string'] = $mValue;
			}
			if( is_string( $mValue ) ) {
				$this['query']['simple_query_string'] = [
					'query' => $mValue,
					'default_operator' => 'and'
				];
			}
			return $this;
	}

	/**
	 *
	 * @return LookUp
	 */
	public function getSimpleQueryString() {
		if( !isset( $this['query'] ) || !isset( $this['query']['simple_query_string'] ) ) {
			return null;
		}
		return $this['query']['simple_query_string'];
	}

	/**
	 *
	 * @return LookUp
	 */
	public function clearSimpleQueryString() {
		$this->ensurePropertyPath( 'query.simple_query_string', [] );
		unset( $this['query']['simple_query_string'] );
		return $this;
	}

	/**
	 * Example for complex filter
	 *
	 * "query" => [
	 *       "bool" => [
	 *           "filter" => [[
	 *               "terms" => [ "entitydata.parentid" => [ 0 ] ]
	 *           ],[
	 *               "terms" => [ "entitydata.type" => [ "microblog", "profile" ] ]
	 *           ]]
	 *       ]
	 *   ]
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-bool-query.html
	 * @param string $sFieldName
	 * @param string|array $mValue
	 * @return LookUp
	 */
	public function addFilter( $sFieldName, $mValue ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		if( !is_array( $mValue ) ) {
			$mValue = [ $mValue ];
		}

		//HINT: "[terms] query does not support multiple fields" - Therefore we
		//need to make a dedicated { "terms" } object for each field
		$bAppededExistingFilter = false;
		for( $i = 0; $i < count( $this['query']['bool']['filter'] ); $i++ ) {
			$aFilter = &$this['query']['bool']['filter'][$i];

			//Append
			if( isset( $aFilter['terms'] ) && isset( $aFilter['terms'][$sFieldName] ) ) {
				$aFilter['terms'][$sFieldName] = array_merge( $aFilter['terms'][$sFieldName],  $mValue );
				$aFilter['terms'][$sFieldName] = array_unique( $aFilter['terms'][$sFieldName] );
				$aFilter['terms'][$sFieldName] = array_values( $aFilter['terms'][$sFieldName] ); //reset indices

				$bAppededExistingFilter = true;
			}
		}

		if( !$bAppededExistingFilter ) {
			$this['query']['bool']['filter'][] = [
				'terms' => [
					$sFieldName => $mValue
				]
			];
		}

		return $this;
	}

	/**
	 *
	 * @param string $sFieldName
	 * @param string|array $mValue
	 * @return LookUp
	 */
	public function removeFilter( $sFieldName, $mValue ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		if( !is_array( $mValue ) ) {
			$mValue = [ $mValue ];
		}

		for( $i = 0; $i < count( $this['query']['bool']['filter'] ); $i++ ) {
			$aFilter = &$this['query']['bool']['filter'][$i];
			if( !isset( $aFilter['terms'][$sFieldName] ) ) {
				continue;
			}

			$aFilter['terms'][$sFieldName] = array_diff( $aFilter['terms'][$sFieldName], $mValue );
			$aFilter['terms'][$sFieldName] = array_values( $aFilter['terms'][$sFieldName] );

			if( empty( $aFilter['terms'][$sFieldName] ) ) {
				unset( $this['query']['bool']['filter'][$i] );
			}

		}

		$this['query']['bool']['filter'] = array_values( $this['query']['bool']['filter'] );

		return $this;

	}

	/**
	 * Example for complex sort
	 *
	 * "sort"  => [
     *     [ "post_date"  => ["order"  => "asc"]],
     *     "user",
     *     [ "name"  => "desc" ],
     *     [ "age"  => "desc" ],
     *     "_score"
     * ]
	 *
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.2/search-request-sort.html
	 * @param string $sFieldName
	 * @param string|array $mOrder
	 * @return LookUp
	 */
	public function addSort( $sFieldName, $mOrder = null ) {
		$this->ensurePropertyPath( 'sort', [] );
		if( $mOrder === null ) {
			$mOrder = self::SORT_ASC;
		}

		if( is_string( $mOrder ) ) {
			$mOrder = [
				"order" => $mOrder
			];
		}

	$replacedExistingSort = false;
	for( $i = 0; $i < count( $this['sort'] ); $i++ ) {
		$sorter = &$this['sort'][$i];
		if( isset( $sorter[$sFieldName] ) ) {
			$sorter[$sFieldName] = $mOrder;
			$replacedExistingSort = true;
		}
	}

	if( !$replacedExistingSort ) {
		$this['sort'][] = [
			$sFieldName => $mOrder
		];
	}

	return $this;
	}

	/**
	 *
	 * @param string $sFieldName
	 * @return LookUp
	 */
	public function removeSort( $sFieldName ) {
		$this->ensurePropertyPath( 'sort', [] );

		$newSort = [];
		for( $i = 0; $i < count( $this['sort'] ); $i++ ) {
			$sorter = $this['sort'][$i];
			if( isset($sorter[$sFieldName]) ) {
				continue;
			}
			$newSort[] = $sorter;
		}

		$this['sort'] = $newSort;

		if( empty( $this['sort'] ) ) {
			unset( $this['sort'] );
		}

		return $this;
	}
}