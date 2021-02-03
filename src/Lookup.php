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
		if ( is_array( $aConfig ) ) {
			foreach ( $aConfig as $sKey => $mValue ) {
				$this[$sKey] = $mValue;
			}
		}
	}

	/**
	 *
	 * @param string $sPath
	 * @param mixed $mDefault
	 * @param null &$aBase - deprecated
	 */
	protected function ensurePropertyPath( $sPath, $mDefault, &$aBase = null ) {
		$aPathParts = explode( '.', $sPath );

		$current = $this;
		foreach ( $aPathParts as $sPathPart ) {
			if ( !isset( $current[$sPathPart] ) ) {
				$current[$sPathPart] = [];
			}
			$current = &$current[$sPathPart];
		}

		if ( empty( $current ) ) {
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
	 * "query" : {
	 *   "bool": {
	 *     "must": {
	 *       "query_string": {
	 *         "query" : "Steve"
	 *       }
	 *     },
	 *     "filter": [{
	 *       "terms": { "_type": ["wikipage", "repofile"] }
	 *     }]
	 *   }
	 * }
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.2/query-dsl-query-string-query.html
	 * @param string|array $mValue
	 * @return Lookup
	 */
	public function setQueryString( $mValue ) {
		$this->ensurePropertyPath( 'query.bool.must', [] );

		// There must not be more than on "query_string" in "must"
		foreach ( $this['query']['bool']['must'] as $iIndex => $aMust ) {
			if ( isset( $aMust['query_string'] ) ) {
				unset( $this['query']['bool']['must'][$iIndex] );
			}
		}

		if ( is_array( $mValue ) ) {
			$this['query']['bool']['must'][] = [
				'query_string' => $mValue
			];
		}
		if ( is_string( $mValue ) ) {
			$this['query']['bool']['must'][] = [
				'query_string' => [
					'query' => $mValue,
					'default_operator' => 'AND'
				]
			];
		}

		$this['query']['bool']['must'] = array_values( $this['query']['bool']['must'] );

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getQueryString() {
		$this->ensurePropertyPath( 'query.bool.must', [] );
		foreach ( $this['query']['bool']['must'] as $iIndex => $aMust ) {
			if ( isset( $aMust['query_string'] ) ) {
				return $aMust['query_string'];
			}
		}

		return [];
	}

	/**
	 *
	 * @return Lookup
	 */
	public function clearQueryString() {
		$this->ensurePropertyPath( 'query.bool.must', [] );
		foreach ( $this['query']['bool']['must'] as $iIndex => $aMust ) {
			if ( isset( $aMust['query_string'] ) ) {
				unset( $this['query']['bool']['must'][$iIndex] );
			}
		}
		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @param string $value
	 * @return Lookup
	 */
	public function setMatchQueryString( $field, $value ) {
		$this->ensurePropertyPath( 'query.match', [] );
		$this['query']['match'] = [
			$field => [
				"query" => $value
			]
		];

		return $this;
	}

	/**
	 *
	 * @return Lookup
	 */
	public function removeMatchQuery() {
		$this->ensurePropertyPath( 'query.match', [] );
		unset( $this['query']['match'] );

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @param int $fuzziness
	 * @param array|null $options
	 * @return Lookup
	 */
	public function setBoolMatchQueryFuzziness( $field, $fuzziness, $options = [] ) {
		$this->ensurePropertyPath( 'query.bool.must.match.' . $field, [] );
		$options['fuzziness'] = $fuzziness;

		$this['query']['bool']['must']['match'][$field] = array_merge(
			$this['query']['bool']['must']['match'][$field],
			$options
		);

		return $this;
	}

	/**
	 * Sets match query string in Bool query
	 *
	 * @param string $field
	 * @param string $value
	 * @return Lookup
	 */
	public function setBoolMatchQueryString( $field, $value ) {
		$this->ensurePropertyPath( 'query.bool.must', [] );
		$this['query']['bool']['must'] = [
			"match" => [
				$field => [
					"query" => $value
				]
			]
		];

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @param string|array $value
	 * @return Lookup
	 */
	public function addBoolMustNotTerms( $field, $value ) {
		$this->ensurePropertyPath( 'query.bool.must_not', [] );

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		foreach ( $this['query']['bool']['must_not'] as &$terms ) {
			if ( isset( $terms['terms'][$field] ) ) {
				$terms['terms'][$field] = array_merge(
					$terms['terms'][$field],
					$value
				);
				return $this;
			}
		}

		$this['query']['bool']['must_not'][]['terms'] = [
			$field => $value
		];

		return $this;
	}

	/**
	 * Remove items from must_not terms by field and value
	 *
	 * @param string $field
	 * @param string|array $value
	 * @return Lookup
	 */
	public function removeBoolMustNotTerms( $field, $value ) {
		return $this->removeTerms( 'must_not', $field, $value );
	}

	/**
	 * Get unified array of must_not values
	 *
	 * @return array
	 */
	public function getMustNots() {
		return $this->getCompounded( 'must_not' );
	}

	/**
	 *
	 * @param string $field
	 * @return Lookup
	 */
	public function removeBoolMustNot( $field ) {
		$this->ensurePropertyPath( 'query.bool.must_not', [] );
		foreach ( $this['query']['bool']['must_not'] as $idx => $terms ) {
			if ( isset( $terms['terms'][$field] ) ) {
				unset( $this['query']['bool']['must_not'][$idx] );
			}
		}

		$this['query']['bool']['must_not'] = array_values( $this['query']['bool']['must_not'] );

		return $this;
	}

	/**
	 * Removes all values for a filter field regardless of the value
	 *
	 * @param string $field
	 * @return Lookup
	 */
	public function clearFilter( $field ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );
		foreach ( $this['query']['bool']['filter'] as $idx => $filter ) {
			if ( isset( $filter['terms'] ) && isset( $filter['terms'][$field] ) ) {
				unset( $this['query']['bool']['filter'][$idx]['terms'][$field] );
				if ( empty( $this['query']['bool']['filter'][$idx]['terms'] ) ) {
					unset( $this['query']['bool']['filter'][$idx] );
				}
			}
			if ( isset( $filter['term'] ) && isset( $filter['term'][$field] ) ) {
				unset( $this['query']['bool']['filter'][$idx] );
			}
		}

		if ( empty( $this['query']['bool']['filter'] ) ) {
			unset( $this['query']['bool']['filter'] );
		} else {
			// reindex the array
			$this['query']['bool']['filter'] = array_values( $this['query']['bool']['filter'] );
		}

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

		if ( !is_array( $mValue ) ) {
			$mValue = [ $mValue ];
		}

		// HINT: "[terms] query does not support multiple fields" - Therefore we
		// need to make a dedicated { "terms" } object for each field
		$bAppededExistingFilter = false;
		for ( $i = 0; $i < count( $this['query']['bool']['filter'] ); $i++ ) {
			$aFilter = &$this['query']['bool']['filter'][$i];

			// Append
			if ( isset( $aFilter['terms'] ) && isset( $aFilter['terms'][$sFieldName] ) ) {
				$aFilter['terms'][$sFieldName] = array_merge( $aFilter['terms'][$sFieldName],  $mValue );
				$aFilter['terms'][$sFieldName] = array_unique( $aFilter['terms'][$sFieldName] );
				// reset indices
				$aFilter['terms'][$sFieldName] = array_values( $aFilter['terms'][$sFieldName] );

				$bAppededExistingFilter = true;
			}
		}

		if ( !$bAppededExistingFilter ) {
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
	 * @param string $value
	 * @return Lookup
	 */
	public function addTermFilter( $field, $value ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		foreach ( $this['query']['bool']['filter'] as $filter ) {
			if ( isset( $filter['term'] ) && isset( $filter['term'][$field] ) && $filter['term'][$field] == $value ) {
				// Filter already set - nothing to do
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
	 * @param string $field
	 * @param string|array $value
	 * @return Lookup
	 */
	public function removeTermsFilter( $field, $value ) {
		return $this->removeTerms( 'filter', $field, $value );
	}

	/**
	 * @param string $prop
	 * @param string $field
	 * @param string|array $value
	 * @return Lookup
	 */
	public function removeTerms( $prop, $field, $value ) {
		$this->ensurePropertyPath( "query.bool.$prop", [] );

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		for ( $i = 0; $i < count( $this['query']['bool'][$prop] ); $i++ ) {
			$item = &$this['query']['bool'][$prop][$i];
			if ( !isset( $item['terms'][$field] ) ) {
				continue;
			}

			$item['terms'][$field] = array_diff( $item['terms'][$field], $value );
			$item['terms'][$field] = array_values( $item['terms'][$field] );

			if ( empty( $item['terms'][$field] ) ) {
				unset( $this['query']['bool'][$prop][$i] );
			}

		}

		$this['query']['bool'][$prop] = array_values( $this['query']['bool'][$prop] );

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

		foreach ( $this['query']['bool']['filter'] as $key => $filter ) {
			if ( isset( $filter['term'] ) && isset( $filter['term'][$field] ) && $filter['term'][$field] == $value ) {
				unset( $this['query']['bool']['filter'][$key] );
			}
		}

		if ( empty( $this['query']['bool']['filter'] ) ) {
			unset( $this['query']['bool']['filter'] );
		} else {
			$this['query']['bool']['filter'] = array_values( $this['query']['bool']['filter'] );
		}

		return $this;
	}

	/**
	 * Returns formatted list of all filters by type, in form:
	 * [
	 * 		"type1" => [
	 * 			"field1" => [1,2],
	 * 			"field2" => ["Value"]
	 * 		],
	 * 		"type2" => [
	 * 			"field3" => [0,1]
	 * 		]
	 * ]
	 *
	 * Types ATM are terms (for OR filters) and term (for AND filters)
	 * @return array
	 */
	public function getFilters() {
		return $this->getCompounded( 'filter' );
	}

	/**
	 * Get unified array of values for given property
	 *
	 * @param string $prop
	 * @return array
	 */
	public function getCompounded( $prop ) {
		$this->ensurePropertyPath( "query.bool.$prop", [] );

		$items = [];
		foreach ( $this['query']['bool'][$prop] as $idx => $item ) {
			foreach ( $item as $typeName => $typeField ) {
				if ( !isset( $items[$typeName] ) ) {
					$items[$typeName] = [];
				}
				foreach ( $typeField as $fieldName => $fieldValue ) {
					if ( !isset( $items[$typeName][$fieldName] ) ) {
						$items[$typeName][$fieldName] = [];
					}
					if ( is_array( $fieldValue ) ) {
						$items[$typeName][$fieldName] = array_merge(
							$items[$typeName][$fieldName],
							$fieldValue
						);
					} else {
						$items[$typeName][$fieldName][] = $fieldValue;
					}
				}
			}
		}
		return $items;
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
	 * @param string|array|null $mOrder
	 * @return Lookup
	 */
	public function addSort( $sFieldName, $mOrder = null ) {
		$this->ensurePropertyPath( 'sort', [] );
		if ( $mOrder === null ) {
			$mOrder = self::SORT_ASC;
		}

		if ( is_string( $mOrder ) ) {
			$mOrder = [
				"order" => $mOrder
			];
		}

		$replacedExistingSort = false;
		for ( $i = 0; $i < count( $this['sort'] ); $i++ ) {
			$sorter = &$this['sort'][$i];
			if ( isset( $sorter[$sFieldName] ) ) {
				$sorter[$sFieldName] = $mOrder;
				$replacedExistingSort = true;
			}
		}

		if ( !$replacedExistingSort ) {
			$this['sort'][] = [
				$sFieldName => $mOrder
			];
		}

		return $this;
	}

	/**
	 *
	 * @param string|null $sFieldName If null, all sorts will be removed
	 * @return Lookup
	 */
	public function removeSort( $sFieldName = false ) {
		$this->ensurePropertyPath( 'sort', [] );

		if ( !$sFieldName ) {
			$this['sort'] = [];
			return $this;
		}

		$newSort = [];
		for ( $i = 0; $i < count( $this['sort'] ); $i++ ) {
			$sorter = $this['sort'][$i];
			if ( isset( $sorter[$sFieldName] ) ) {
				continue;
			}
			$newSort[] = $sorter;
		}

		$this['sort'] = $newSort;

		if ( empty( $this['sort'] ) ) {
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
	 *
	 * @param array|string $value
	 * @return Lookup
	 */
	public function setSearchAfter( $value ) {
		$this->ensurePropertyPath( 'search_after', [] );

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		unset( $this->from );

		$this->search_after = $value;

		return $this;
	}

	/**
	 *
	 * @return Lookup
	 */
	public function remoteSearchAfter() {
		$this->ensurePropertyPath( 'search_after', [] );

		unset( $this->search_after );

		return $this;
	}

	/**
	 * Replaces entire sort array with the one supplied
	 *
	 * @param array $sort
	 * @return Lookup
	 */
	public function setSort( $sort ) {
		$this['sort'] = $sort;
		return $this;
	}

	/**
	 * Adds "should" clause to boolean query
	 *
	 * "query" => [
	 *       "bool" => [
	 *           "should" => [[
	 *               "terms" => [ "entitydata.parentid" => [ 0 ] ]
	 *           ],[
	 *               "terms" => [ "entitydata.type" => [ "microblog", "profile" ] ]
	 *           ]]
	 *       ]
	 *   ]
	 *
	 * Since "should" is inheritly optional we can put all values
	 * under single "terms"
	 *
	 * @param string $field
	 * @param string|array $value
	 * @return Lookup
	 */
	public function addShould( $field, $value ) {
		return $this->addShouldTerms( $field, $value );
	}

	/**
	 *
	 * @param string $field
	 * @param string $value
	 * @param int $boost
	 * @param bool $append
	 * @return Lookup
	 */
	public function addShouldTerms( $field, $value, $boost = 1, $append = true ) {
		$this->ensurePropertyPath( 'query.bool.should', [] );

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		$appended = false;
		if ( $append ) {
			foreach ( $this['query']['bool']['should'] as $idx => &$should ) {
				if ( !isset( $should['terms'][$field] ) ) {
					continue;
				}
				$value = array_diff( $value, $should['terms'][$field] );
				if ( empty( $value ) ) {
					// Nothing new to add
					return $this;
				}

				$should['terms'][$field] = array_merge( $should['terms'][$field], $value );
				$appended = true;
			}
		}

		if ( !$appended ) {
			$this['query']['bool']['should'][] = [
				"terms" => [
					$field => $value,
					"boost" => $boost
				]
			];
		}

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @param string $value
	 * @param bínt $boost
	 * @return Lookup
	 */
	public function addShouldMatch( $field, $value, $boost = 1 ) {
		$this->ensurePropertyPath( 'query.bool.should', [] );

		foreach ( $this['query']['bool']['should'] as $idx => &$should ) {
			if ( !isset( $should['match'] ) || !isset( $should['match'][$field] ) ) {
				continue;
			}
			$should['match'][$field] = [
				"query" => $value,
				"boost" => $boost
			];
			return $this;
		}

		$this['query']['bool']['should'][] = [
			"match" => [
				$field => [
					"query" => $value,
					"boost" => $boost
				]
			]
		];

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @param string $value
	 * @return Lookup
	 */
	public function removeShould( $field, $value = [] ) {
		return $this->removeShouldTerms( $field, $value );
	}

	/**
	 *
	 * @param string $field
	 * @param string|array|null $value If not supplied, entire field will be removed
	 * @return Lookup
	 */
	public function removeShouldTerms( $field, $value = [] ) {
		$this->ensurePropertyPath( 'query.bool.should', [] );

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		foreach ( $this['query']['bool']['should'] as $idx => &$should ) {
			if ( !isset( $should['terms'][$field] ) ) {
				continue;
			}

			$oldValues = $should['terms'][$field];
			$newValues = array_values( array_diff( $oldValues, $value ) );
			if ( empty( $newValues ) || empty( $value ) ) {
				unset( $this['query']['bool']['should'][$idx] );
				continue;
			}
			$should['terms'][$field] = $newValues;
		}

		$this['query']['bool']['should'] = array_values( $this['query']['bool']['should'] );

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @return Lookup
	 */
	public function removeShouldMatch( $field ) {
		$this->ensurePropertyPath( 'query.bool.should', [] );

		$newShoulds = [];
		foreach ( $this['query']['bool']['should'] as $idx => &$should ) {
			if ( !isset( $should['match'] ) || !isset( $should['match'][$field] ) ) {
				$newShoulds[] = $should;
			}
		}

		$this['query']['bool']['should'] = $newShoulds;

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getShould() {
		$this->ensurePropertyPath( 'query.bool.should', [] );
		return $this['query']['bool']['should'];
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
		foreach ( $aFieldNames as $sFieldNamePart ) {
			if ( !isset( $aBase['aggs'] ) ) {
				$aBase['aggs'] = [];
			}

			$aBase['aggs']['field_' . $sFieldNamePart] = [
				'terms' => [
					'field' => $sFieldNamePart,
					'size' => 1000
				]
			];

			$aBase = &$aBase['aggs']['field_' . $sFieldNamePart];
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
		foreach ( $aFieldNames as $sFieldNamePart ) {
			if ( !isset( $aBase['aggs'] ) ) {
				continue;
			}
			$aNode = &$aBase;
			$sLeafFieldName = $sFieldNamePart;
			$aBase = &$aBase['aggs']['field_' . $sFieldNamePart];
		}

		if ( isset( $aNode['aggs']['field_' . $sLeafFieldName] ) ) {
			unset( $aNode['aggs']['field_' . $sLeafFieldName] );
		}

		if ( empty( $aNode['aggs'] ) ) {
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
		foreach ( $aFieldNames as $sFieldNamePart ) {
			if ( !isset( $aBase['highlight'] ) ) {
				$aBase['highlight'] = [];
			}
			if ( !isset( $aBase['highlight']['fields'] ) ) {
				$aBase['highlight']['fields'] = [];
			}

			$aBase['highlight']['fields'][$sFieldNamePart] = [
				'matched_fields' => [
					$sFieldNamePart
				],
				'pre_tags' => [ "<b>" ],
				'post_tags' => [ "</b>" ]
			];
		}

		return $this;
	}

	/**
	 * Removes single field from the highligh array
	 *
	 * @param string $field
	 * @return Lookup
	 */
	public function removeHighlighter( $field ) {
		if ( isset( $this['highlight']['fields'][$field] ) ) {
			unset( $this['highlight']['fields'][$field] );
		}
		if ( empty( $this['highlight']['fields'] ) ) {
			unset( $this['highlight'] );
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
	 * @return bool|int
	 */
	public function getSize() {
		if ( isset( $this['size'] ) ) {
			return (int)$this['size'];
		}
		return false;
	}

	/**
	 * Adds a field or fields to the set of fields which
	 * will be returned in the _source key in result
	 *
	 * @param string|array $field
	 * @return Lookup
	 */
	public function addSourceField( $field ) {
		$this->ensurePropertyPath( '_source', [] );

		if ( !is_array( $field ) ) {
			$field = [ $field ];
		}

		$this['_source'] = array_merge( $this['_source'], $field );

		return $this;
	}

	/**
	 * Removes field/fields from _source param
	 *
	 * @param string|array $field
	 * @return Lookup
	 */
	public function removeSourceField( $field ) {
		$this->ensurePropertyPath( '_source', [] );

		if ( !is_array( $field ) ) {
			$field = [ $field ];
		}

		$newSource = [];
		foreach ( $this['_source'] as $sourceField ) {
			if ( in_array( $sourceField, $field ) ) {
				continue;
			}
			$newSource[] = $sourceField;
		}

		if ( empty( $newSource ) ) {
			unset( $this['_source'] );
		} else {
			$this['_source'] = $newSource;
		}

		return $this;
	}

	/**
	 * Completely removed _source key, meaning all available fields
	 * will be returned
	 *
	 * @return Lookup
	 */
	public function clearSourceField() {
		$this->ensurePropertyPath( '_source', [] );
		unset( $this['_source'] );

		return $this;
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
	 * @return bool|\BS\ExtendedSearch\Lookup
	 */
	public function getFrom() {
		if ( isset( $this['from'] ) ) {
			return $this['from'];
		}
		return false;
	}

	/**
	 *
	 * @param string $field
	 * @param string $value
	 * @return Lookup
	 */
	public function addSuggest( $field, $value ) {
		$base = $this;
		$base->ensurePropertyPath( 'suggest', [] );

		$base['suggest'][$field] = [
			'text' => $value,
			'term' => [
				'field' => $field
			]
		];

		return $this;
	}

	/**
	 *
	 * @param string $field
	 * @return Lookup
	 */
	public function removeSuggest( $field ) {
		$base = $this;
		$base->ensurePropertyPath( 'suggest', [] );

		if ( isset( $base['suggest'][$field] ) ) {
			unset( $base['suggest'][$field] );
		}

		return $this;
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

		if ( !isset( $base['suggest'] ) ) {
			return $this;
		}

		if ( !isset( $base['suggest'][$field] ) ) {
			return $this;
		}

		unset( $base['suggest'][$field] );

		if ( empty( $base['suggest'] ) ) {
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

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		if ( !isset( $base['suggest'][$acField] ) ) {
			return $this;
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

		if ( !isset( $base['suggest'][$acField] ) ) {
			return $this;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.contexts.$contextField", [] );

		unset( $base['suggest'][$acField]['completion']['contexts'][$contextField] );
		if ( empty( $base['suggest'][$acField]['completion']['contexts'] ) ) {
			unset( $base['suggest'][$acField]['completion']['contexts'] );
		}

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

		if ( !isset( $base['suggest'][$acField] ) ) {
			return $this;
		}

		$this->ensurePropertyPath( "suggest.$acField.completion.contexts.$contextField", [] );

		$newContextFields = [];
		foreach ( $base['suggest'][$acField]['completion']['contexts'][$contextField] as $field ) {
			if ( $field === $value ) {
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

		if ( !isset( $base['suggest'][$acField] ) ) {
			return $this;
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

		if ( !isset( $base['suggest'][$acField] ) ) {
			return $this;
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

		if ( !isset( $base['suggest'][$acField] ) ) {
			return $this;
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

	/**
	 *
	 * @return Lookup
	 */
	public function setForceTerm() {
		$this->ensurePropertyPath( 'forceTerm', true );
		return $this;
	}

	/**
	 *
	 * @return Lookup
	 */
	public function removeForceTerm() {
		$this->ensurePropertyPath( 'forceTerm', true );
		unset( $this['forceTerm'] );

		return $this;
	}

	/**
	 *
	 * @return bool
	 */
	public function getForceTerm() {
		if ( isset( $this['forceTerm'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sets the query for "more like this" search
	 * MLT query will replace all content previously set to the Lookup
	 *
	 * @param string $docId
	 * @param array $fields
	 * @param array $options
	 * @param string $indexName
	 * @return Lookup
	 */
	public function setMLTQuery( $docId, $fields, $options = [], $indexName = '' ) {
		$options = array_merge( [
			"min_term_freq" => 3,
			"max_query_terms" => 50
		], $options );

		$this['query'] = [
			"more_like_this" => array_merge( [
				"fields" => $fields,
				"like" => [
					"_id" => $docId
				]
			], $options )
		];

		if ( $indexName !== '' ) {
			$this['query']['more_like_this']['like']['_index'] = $indexName;
		}

		return $this;
	}
}
