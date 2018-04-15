<?php

namespace BS\ExtendedSearch;

/**
 * Represents a query that gets send to Elastic Search
 */
class Lookup extends \ArrayObject {

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';
	const TYPE_FIELD_NAME = '_type';

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
	 * Removes all values for a filter field regardless of the value
	 *
	 * @return Lookup
	 */
	public function clearFilter( $field ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );
		foreach( $this['query']['bool']['filter'] as $idx => $filter ) {
			if( isset( $filter['terms'] ) && isset( $filter['terms'][$field] ) ) {
				unset( $filter['terms'][$field] );
			}
			if( isset( $filter['term'] ) && isset( $filter['term'][$field] ) ) {
				unset( $filter['term'][$field] );
			}
		}

		return $this;
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
	public function addTermsFilter( $sFieldName, $mValue ) {
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
	 * Term filter can only hold one value, so we need to make
	 * new filter for each field and value
	 *
	 * @param string $field
	 * @param mixed $value
	 */
	public function addTermFilter( $field, $value ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		foreach( $this['query']['bool']['filter'] as $filter ) {
			if( isset( $filter['term'] ) && isset( $filter['term'][$field] ) && $filter['term'][$field] == $value ) {
				//Filter already set - nothing to do
				return $this;
			}
		}

		$this['query']['bool']['filter'][] = [
			'term' => [
				$field => $value
			]
		];

		return $this;
	}

	/**
	 *
	 * @param string $sFieldName
	 * @param string|array $mValue
	 * @return Lookup
	 */
	public function removeTermsFilter( $field, $value ) {
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
	 *
	 * @param string $field
	 * @param string $value
	 * @return Lookup
	 */
	public function removeTermFilter( $field, $value ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		foreach( $this['query']['bool']['filter'] as $key => $filter ) {
			if( isset( $filter['term'] ) && isset( $filter['term'][$field] ) && $filter['term'][$field] == $value ) {
				unset( $this['query']['bool']['filter'][$key] );
			}
		}

		return $this;
	}

	/**
	 * Returns formatted list of all filters by type, in form:
	 * [
	 *		"type1" => [
	 *			"field1" => [1,2],
	 *			"field2" => ["Value"]
	 *		],
	 *		"type2" => [
	 *			"field3" => [0,1]
	 *		]
	 * ]
	 *
	 * Types ATM are terms (for OR filters) and term (for AND filters)
	 * @return array
	 */
	public function getFilters() {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		$filters = [];
		foreach( $this['query']['bool']['filters'] as $idx => $filter ) {
			foreach( $filter as $typeName => $typeField ) {
				if( !isset( $filters[$typeName] ) ) {
					$filters[$typeName] = [];
				}
				foreach( $typeField as $fieldName => $fieldValue ) {
					if( !isset( $filters[$typeName][$fieldName] ) ) {
						$filters[$typeName][$fieldName] = [];
					}
					if( !is_array( $fieldValue ) ) {
						$filters[$typeName][$fieldName] = array_merge(
							$filters[$typeName][$fieldName],
							$fieldValue
						);
					} else {
						$filters[$typeName][$fieldName][] = $fieldValue;
					}
				}
			}
		}
		return $filters;
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
	 *
	 * @return array
	 */
	public function getSort() {
		$this->ensurePropertyPath( 'sort', [] );
		return $this['sort'];
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
		$aBase = $this;
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

		$aBase = $this;
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

	/**
	 *
	 * @param string $sFieldName e.g. "extension" or even "_type/extension"
	 * @return Lookup
	 */
	public function addHighlighter( $sFieldName ) {
		$aFieldNames = explode( '/', $sFieldName );

		$aBase = $this;
		foreach( $aFieldNames as $sFieldNamePart ) {
			if( !isset( $aBase['highlight'] ) ) {
				$aBase['highlight'] = [];
			}
			if( !isset( $aBase['highlight']['fields'] ) ) {
				$aBase['highlight']['fields'] = [];
			}

			$aBase['highlight']['fields'][$sFieldNamePart] = [
				'matched_fields' => [
					$sFieldNamePart
				]
			];

			$aBase = &$aBase['highlight']['fields'][$sFieldNamePart];
		}

		return $this;
	}

	/**
	 * Sets the default page size
	 *
	 * @param int $size
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function setSize( $size ) {
		$aBase = $this;
		$aBase['size'] = $size;
		return $this;
	}

	/**
	 *
	 * @return boolean|\BS\ExtendedSearch\Lookup
	 */
	public function getSize() {
		if( isset( $this['size'] ) ) {
			return $this['size'];
		}
		return false;
	}

	/**
	 * Sets offset from which to retrieve results
	 *
	 * @param int $from
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function setFrom( $from ) {
		$aBase = $this;
		$aBase['from'] = $from;
		return $this;
	}

	/**
	 *
	 * @return boolean|\BS\ExtendedSearch\Lookup
	 */
	public function getFrom() {
		if( isset( $this['from'] ) ) {
			return $this['from'];
		}
		return false;
	}

	/**
	 *
	 * @param string $field
	 * @param string $value
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function addAutocompleteSuggest( $field, $value ) {
		$base = $this;
		$base->ensurePropertyPath( 'suggest', [] );

		$base['suggest'][$field] = [
			'prefix' => $value,
			'completion' => [
				'field' => $field
			]
		];

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function removeAutocompleteSuggest( $field ) {
		$base = $this;

		if( !isset( $base['suggest'] ) ) {
			return;
		}

		if( !isset( $base['suggest'][$field] ) ) {
			return;
		}

		unset( $base['suggest'][$field] );

		if( empty( $base['suggest'] ) ) {
			unset( $base['suggest'] );
		}

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getAutocompleteSuggest() {
		$this->ensurePropertyPath( 'suggest', [] );
		return $this['suggest'];
	}

	/**
	 * Adds context field to autocomplete suggester
	 * Context serves as a filter
	 *
	 * @param string $acField
	 * @param sting $contextField
	 * @param array|string $value
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function addAutocompleteSuggestContext( $acField, $contextField, $value ) {
		$this->ensurePropertyPath( 'suggest', [] );

		$base = $this;

		if( !is_array( $value ) ) {
			$value = [ $value ];
		}

		if( !( in_array( $acField, $base['suggest'] ) ) ) {
			return;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.contexts", [] );

		$base['suggest'][$acField]['completion']['contexts'][$contextField] = $value;

		return $this;
	}

	/**
	 *
	 * @param string $acField
	 * @param string $contextField
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function removeAutocompleteSuggestContext( $acField, $contextField ) {
		$this->ensurePropertyPath( 'suggest', [] );

		$base = $this;

		if( !( in_array( $acField, $base['suggest'] ) ) ) {
			return;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.contexts.$contextField", [] );

		unset( $base['suggest'][$acField]['completion']['contexts'][$contextField] );

		return $this;
	}

	/**
	 * Removes single field from context fields array
	 *
	 * @param string $acField
	 * @param string $contextField
	 * @param string $value
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function removeAutocompleteSuggestContextValue( $acField, $contextField, $value ) {
		$this->ensurePropertyPath( 'suggest', [] );

		$base = $this;

		if( !( in_array( $acField, $base['suggest'] ) ) ) {
			return;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.contexts.$contextField", [] );

		$newContextFields = [];
		foreach( $base['suggest'][$acField]['completion']['contexts'][$contextField] as $field ) {
			if( $field === $value ) {
				continue;
			}
			$newContextFields[] = $field;
		}

		$base['suggest'][$acField]['completion']['contexts'][$contextField] = $newContextFields;

		return $this;
	}

	/**
	 * Adds level of fuzziness to the autocomplete suggester
	 *
	 * @param string $acField
	 * @param int $fuzzinessLevel
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function addAutocompleteSuggestFuzziness( $acField, $fuzzinessLevel ) {
		$this->ensurePropertyPath( 'suggest', [] );

		$base = $this;

		if( !( in_array( $acField, $base['suggest'] ) ) ) {
			return;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.fuzzy", [] );


		$base['suggest'][$acField]['completion']['fuzzy'] = [
			'fuzziness' => $fuzzinessLevel
		];

		return $this;
	}

	/**
	 *
	 * @param string $acField
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function removeAutocompleteSuggestFuzziness( $acField ) {
		$this->ensurePropertyPath( 'suggest', [] );

		$base = $this;

		if( !( in_array( $acField, $base['suggest'] ) ) ) {
			return;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.fuzzy", [] );


		unset( $base['suggest'][$acField]['completion']['fuzzy'] );

		return $this;
	}

	/**
	 * Sets number of suggestions retrieved for particular field
	 *
	 * @param string $acField
	 * @param int $size
	 * @return \BS\ExtendedSearch\Lookup
	 */
	public function setAutocompleteSuggestSize( $acField, $size ) {
		$this->ensurePropertyPath( 'suggest', [] );

		$base = $this;

		if( !( in_array( $acField, $base['suggest'] ) ) ) {
			return;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion", [] );


		$base['suggest'][$acField]['completion']['size'] = $size;

		return $this;
	}

	/**
	 * Returns completion query ready to be sent to search
	 *
	 * @return array
	 */
	public function getAutocompleteSuggestQuery() {
		return [
			"suggest" => [
				"suggest" => $this->getAutocompleteSuggest()
			]
		];
	}
}
