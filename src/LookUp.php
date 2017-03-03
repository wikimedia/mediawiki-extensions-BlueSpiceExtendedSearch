<?php

namespace BS\ExtendedSearch;

class LookUp extends \ArrayObject {

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

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

		/*
		if( $aBase === null ) {
			$aBase = $this;
		}
		$aPathParts = explode( '.', $sPath );
		if( !( !isset( $aBase[$aPathParts[0]] ) && count( $aPathParts ) === 1 ) ) {
			if( !isset( $aBase[$aPathParts[0]] ) ) {
				$aBase[$aPathParts[0]] = [];
			}
			$aBase = $aBase[$aPathParts[0]];
			array_shift( $aPathParts ); //Remove first element
			if( count( $aPathParts ) > 0 ) {
				$this->ensurePropertyPath( implode( '.', $aPathParts ), $mDefault, $aBase );
			}
		}
		else {
			$aBase[$aPathParts[0]] = $mDefault;
		}
		*/
	}

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

	public function getSimpleQueryString() {
		if( !isset( $this['query'] ) || !isset( $this['query']['simple_query_string'] ) ) {
			return null;
		}
		return $this['query']['simple_query_string'];
	}

	public function clearSimpleQueryString() {
		$this->ensurePropertyPath( 'query.simple_query_string', [] );
		unset( $this['query']['simple_query_string'] );
		return $this;
	}

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
/*
				for( $j = 0 ; $j < count( $aFilter['terms'][$sFieldName] ); ++$j ) {
					for( $k = $j + 1; $k < count( $aFilter['terms'][$sFieldName] ); ++$k ) {
						if( $aFilter['terms'][$sFieldName][$j] === $aFilter['terms'][$sFieldName][$k] )
							$aFilter['terms'][$sFieldName] = array_splice( $aFilter['terms'][$sFieldName], $k--, 1 );
					}
				}
 */
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

	public function removeFilter( $sFieldName, $mValue ) {
		$this->ensurePropertyPath( 'query.bool.filter', [] );

		if( !is_array( $mValue ) ) {
			$mValue = [ $mValue ];
		}

		#$aNewFilters = [];

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

		#$this['query']['bool']['filter'] = $aNewFilters;

		return $this;

	}

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