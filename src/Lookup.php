<?php

namespace BS\ExtendedSearch;

/**
 * Represents a query that gets send to Elastic Search
 */
class Lookup extends \ArrayObject {

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
	 * Sets a type filter
	 * @param array $aTypes
	 * @return Lookup
	 */
	public function setTypes( $aTypes ) {
		$this->clearTypes();
		$this->addFilter( '_type', $aTypes );
		return $this;
	}

	/**
	 *
	 * @return Lookup
	 */
	public function clearTypes() {
		$this->ensurePropertyPath( 'query.bool.filter', [] );
		foreach( $this['query']['bool']['filter'] as $iIndex => $aFilter ) {
			if( isset( $aFilter['terms']['_type'] ) ) {
				unset( $this['query']['bool']['filter'][$iIndex]  );
			}
		}

		$this['query']['bool']['filter'] = array_values( $this['query']['bool']['filter'] );

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getTypes() {
		$this->ensurePropertyPath( 'query.bool.filter', [] );
		foreach( $this['query']['bool']['filter'] as $aFilter ) {
			if( isset( $aFilter['terms']['_type'] ) ) {
				return $aFilter['terms']['_type'];
			}
		}
		return [];
	}

	/**
	 * "query" : {
     *   "bool": {
     *     "must": [{
     *       "simple_query_string": {
     *         "query" : "Steve"
     *       }
     *     }],
     *     "filter": [{
     *       "terms": { "_type": ["wikipage", "repofile"] }
     *     }]
     *   }
     * }
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-simple-query-string-query.html
	 * @param string|array $mValue
	 * @return Lookup
	 */
	public function setSimpleQueryString( $mValue ) {
		$this->ensurePropertyPath( 'query.bool.must', [] );

		//There must not be more than on "simple_query_string" in "must"
		foreach( $this['query']['bool']['must'] as $iIndex => $aMust ) {
			if( isset( $aMust['simple_query_string'] ) ) {
				unset( $this['query']['bool']['must'][$iIndex] );
			}
		}
		
		if( is_array( $mValue ) ) {
			$this['query']['bool']['must'][] = [
				'simple_query_string' => $mValue
			];
		}
		if( is_string( $mValue ) ) {
			$this['query']['bool']['must'][] = [
				'simple_query_string' => [
					'query' => $mValue,
					'default_operator' => 'and'
				]
			];
		}

		$this['query']['bool']['must'] = array_values( $this['query']['bool']['must'] );

		return $this;
	}

	/**
	 *
	 * @return string|null
	 */
	public function getSimpleQueryString() {
		$this->ensurePropertyPath( 'query.bool.must', [] );
		foreach( $this['query']['bool']['must'] as $iIndex => $aMust ) {
			if( isset( $aMust['simple_query_string'] ) ) {
				return $aMust['simple_query_string'];
			}
		}
		return null;
	}

	/**
	 *
	 * @return Lookup
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
	 * @return Lookup
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
	 * @return Lookup
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
	 * @return Lookup
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
	 * @return Lookup
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

	/**
	 * "aggs": {
     *  "field__type": {
     *    "terms": {
     *      "field": "_type"
     *    },
     *    "aggs": {
     *     "field_extension" : {
     *       "terms": {
     *         "field": "extension"
     *       }
     *     }
     *   }
     *  },
     *  "field_extension" : {
     *       "terms": {
     *         "field": "extension"
     *       }
     *     }
     * }
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-terms-aggregation.html
	 *
	 * @param string $sFieldName e.g. "extension" or even "_type/extension" to build recursive
	 * @return Lookup
	 */
	public function setBucketTermsAggregation( $sFieldName ) {
		$aFieldNames = explode( '/', $sFieldName );
		$aBase = &$this;
		foreach( $aFieldNames as $sFieldNamePart ) {
			if( !isset( $aBase['aggs'] ) ) {
				$aBase['aggs'] = [];
			}

			$aBase['aggs']['field_'.$sFieldNamePart] = [
				'terms' => [
					'field' => $sFieldNamePart
				]
			];

			$aBase = &$aBase['aggs']['field_'.$sFieldNamePart];
		}

		return $this;
	}

	/**
	 *
	 * @param string $sFieldName e.g. "extension" or even "_type/extension"
	 * @return Lookup
	 */
	public function removeBucketTermsAggregation( $sFieldName ) {
		$aFieldNames = explode( '/', $sFieldName );

		$aBase = &$this;
		$aNode = [];
		$sLeafFieldName = '';
		foreach( $aFieldNames as $sFieldNamePart ) {
			if( !isset( $aBase['aggs'] ) ) {
				continue;
			}
			$aNode = &$aBase;
			$sLeafFieldName = $sFieldNamePart;
			$aBase = &$aBase['aggs']['field_'.$sFieldNamePart];
		}

		if( isset( $aNode['aggs']['field_'.$sLeafFieldName] ) ) {
			unset( $aNode['aggs']['field_'.$sLeafFieldName] );
		}

		if( empty( $aNode['aggs'] ) ) {
			unset( $aNode['aggs'] );
		}

		return $this;
	}
}