/* eslint-disable camelcase */
bs.extendedSearch.Lookup = function ( config ) {
	for ( const field in config ) {
		if ( !config.hasOwnProperty( field ) ) {
			continue;
		}
		if ( typeof config[ field ] === 'function' ) {
			continue;
		}

		if ( this[ field ] ) {
			continue;
		}

		this[ field ] = config[ field ];
	}
};
OO.initClass( bs.extendedSearch.Lookup );

bs.extendedSearch.Lookup.SORT_ASC = 'asc';
bs.extendedSearch.Lookup.SORT_DESC = 'desc';

/**
 * @private
 * @param {string} path
 * @param {Object} initialValue
 * @param {Object} base
 */
bs.extendedSearch.Lookup.prototype.ensurePropertyPath = function ( path, initialValue, base ) {
	base = base || this;
	const pathParts = path.split( '.' );
	if ( !( !base[ pathParts[ 0 ] ] && pathParts.length === 1 ) ) {
		base[ pathParts[ 0 ] ] = base[ pathParts[ 0 ] ] || {};
		base = base[ pathParts[ 0 ] ];
		pathParts.shift(); // Remove first element
		if ( pathParts.length > 0 ) {
			this.ensurePropertyPath( pathParts.join( '.' ), initialValue, base );
		}
	} else {
		base[ pathParts[ 0 ] ] = initialValue;
	}
};

/**
 * @param {string} field
 * @param {string} q
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.setMatchQueryString = function ( field, q ) {
	this.ensurePropertyPath( 'query.match', {} );
	this.query.match[ field ] = { query: q };

	return this;
};

/**
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeMatchQuery = function () {
	this.ensurePropertyPath( 'query.match', {} );
	delete ( this.query.match );

	return this;
};

/**
 * @param {string} field
 * @param {number} fuzziness
 * @param {Array | null} options
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.setBoolMatchQueryFuzziness = function ( field, fuzziness, options ) {
	options = options || {};

	options.fuzziness = fuzziness;

	this.ensurePropertyPath( 'query.bool.must.match.' + field, {} );
	this.query.bool.must.match[ field ] = $.extend( this.query.bool.must.match[ field ], options ); // eslint-disable-line no-jquery/no-extend

	return this;
};

/**
 * Sets match query string
 *
 * @param {string} field
 * @param {string} q
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.setBoolMatchQueryString = function ( field, q ) {
	this.ensurePropertyPath( 'query.bool', {} );

	const must = { match: {} };
	must.match[ field ] = { query: q };

	this.query.bool.must = must;

	return this;
};

bs.extendedSearch.Lookup.prototype.setMultiMatchQuery = function ( field, q ) {
	this.ensurePropertyPath( 'query.bool.must', {} );

	this.query.bool.must.multi_match = {
		query: q,
		type: 'bool_prefix',
		fields: [
			field,
			field + '._2gram',
			field + '._3gram',
			field + '_extra',
			field + '_extra._2gram',
			field + '_extra._3gram'
		]
	};

	return this;
};

/**
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.x/query-dsl-query-string-query.html
 * @param {string|object} q
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.setQueryString = function ( q ) {
	this.ensurePropertyPath( 'query.bool.must', [] );
	const newMusts = [];

	// There must not be more than on "query_string" in "must"
	for ( let i = 0; i < this.query.bool.must.length; i++ ) {
		if ( 'query_string' in this.query.bool.must[ i ] ) {
			continue;
		}
		newMusts.push( this.query.bool.must[ i ] );
	}

	this.query.bool.must = newMusts;

	if ( typeof q === 'object' ) {
		this.query.bool.must.push( {
			query_string: q
		} );
	}
	if ( typeof q === 'string' ) {
		this.query.bool.must.push( {
			query_string: {
				query: q,
				default_operator: 'AND'
			}
		} );
	}
	return this;
};

/**
 * @return {Array}
 */
bs.extendedSearch.Lookup.prototype.getQueryString = function () {
	this.ensurePropertyPath( 'query.bool.must', [] );

	for ( let i = 0; i < this.query.bool.must.length; i++ ) {
		if ( 'query_string' in this.query.bool.must[ i ] ) {
			return this.query.bool.must[ i ].query_string;
		}
	}

	return [];
};

/**
 * Warning: Use carefully! Removing "must" containing
 * query_string will not stop the query from being executed
 * with other parts of bool query, may lead to unexpected results
 *
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.clearQueryString = function () {
	this.ensurePropertyPath( 'query.bool.must', {} );

	for ( let i = 0; i < this.query.bool.must.length; i++ ) {
		const must = this.query.bool.must[ i ];

		if ( 'query_string' in must ) {
			this.query.bool.must.splice( i, 1 );
		}
	}
	return this;
};

bs.extendedSearch.Lookup.prototype.addBoolMustNotTerms = function ( field, value ) {
	this.ensurePropertyPath( 'query.bool.must_not', [] );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	for ( let idx = 0; idx < this.query.bool.must_not.length; idx++ ) {
		const terms = this.query.bool.must_not[ idx ];
		if ( terms.terms[ field ] ) {
			this.query.bool.must_not[ idx ].terms[ field ] = $.merge(
				terms.terms[ field ],
				value
			);
			return this;
		}
	}

	const newMustNot = { terms: {} };
	newMustNot.terms[ field ] = value;
	this.query.bool.must_not.push( newMustNot );

	return this;
};

bs.extendedSearch.Lookup.prototype.removeBoolMustNotTerms = function ( field, value ) {
	return this.removeTerms( 'must_not', field, value );
};

/**
 * Removes field from must_not clause
 *
 * @param {string} field
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeBoolMustNot = function ( field ) {
	this.ensurePropertyPath( 'query.bool.must_not', [] );

	const newMustNots = [];
	for ( let idx = 0; idx < this.query.bool.must_not.length; idx++ ) {
		const terms = this.query.bool.must_not[ idx ];
		for ( const fieldName in terms.terms ) {
			if ( !terms.terms.hasOwnProperty( fieldName ) ) {
				continue;
			}
			if ( fieldName === field ) {
				continue;
			}
			newMustNots.push( terms );
		}
	}

	if ( newMustNots.length === 0 ) {
		delete ( this.query.bool.must_not );
	} else {
		this.query.bool.must_not = newMustNots;
	}

	return this;
};

/**
 * @return {Object}
 */
bs.extendedSearch.Lookup.prototype.getMustNots = function () {
	return this.getCompounded( 'must_not' );
};

/**
 * @param {string} prop
 * @return {Object}
 */
bs.extendedSearch.Lookup.prototype.getCompounded = function ( prop ) {
	this.ensurePropertyPath( 'query.bool.' + prop, [] );

	const values = {};
	for ( let i = 0; i < this.query.bool[ prop ].length; i++ ) {
		const value = this.query.bool[ prop ][ i ];

		for ( const typeName in value ) {
			if ( !value.hasOwnProperty( typeName ) ) {
				continue;
			}
			if ( !values[ typeName ] ) {
				values[ typeName ] = {};
			}
			for ( const fieldName in value[ typeName ] ) {
				if ( !value[ typeName ].hasOwnProperty( fieldName ) ) {
					continue;
				}
				if ( !values[ typeName ].hasOwnProperty( fieldName ) ) {
					values[ typeName ][ fieldName ] = [];
				}
				const filterValue = value[ typeName ][ fieldName ];
				if ( Array.isArray( filterValue ) ) {
					$.merge( values[ typeName ][ fieldName ], filterValue );
				} else {
					values[ typeName ][ fieldName ].push( filterValue );
				}
			}
		}
	}

	return values;
};

/**
 * Removes filter completely regardless of value
 *
 * @param {string} field
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.clearFilter = function ( field ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	const newFilters = [];
	for ( let i = 0; i < this.query.bool.filter.length; i++ ) {
		const filter = this.query.bool.filter[ i ];
		if ( filter.terms && field in this.query.bool.filter[ i ].terms ) {
			continue;
		}
		if ( filter.term && field in filter.term ) {
			continue;
		}
		newFilters.push( this.query.bool.filter[ i ] );
	}

	delete ( this.query.bool.filter );
	if ( newFilters.length > 0 ) {
		this.query.bool.filter = newFilters;
	}

	// Clear context if linked to a field
	if ( this.context && this.context.key === field ) {
		delete this.context;
	}

	return this;
};

/**
 * Gets all filters in lookup in form:
 * {
 * terms: {
 * field1: [values],
 * field2: [values]
 * },
 * term: {
 * field1: [values],
 * field2: [values]
 * }
 * }
 *
 * @return {Object}
 */
bs.extendedSearch.Lookup.prototype.getFilters = function () {
	return this.getCompounded( 'filter' );
};

/**
 * Example for complex filter
 *
 * "query": {
 * "bool": {
 * "filter": [{
 * "terms": { "entitydata.parentid": [ 0 ] }
 * },{
 * "terms": { "entitydata.type": [ "microblog", "profile" ] }
 * }]
 * }
 * }
 *
 * @param {string} fieldName
 * @param {string | Array} value
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addTermsFilter = function ( fieldName, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	// HINT: "[terms] query does not support multiple fields" - Therefore we
	// need to make a dedicated { "terms" } object for each field
	let appededExistingFilter = false;
	for ( let i = 0; i < this.query.bool.filter.length; i++ ) {
		const filter = this.query.bool.filter[ i ];

		// Append
		if ( filter.terms && fieldName in filter.terms ) {
			filter.terms[ fieldName ] = filter.terms[ fieldName ].concat( value );

			// Clean up duplicates: http://stackoverflow.com/questions/1584370/how-to-merge-two-arrays-in-javascript-and-de-duplicate-items
			for ( let j = 0; j < filter.terms[ fieldName ].length; ++j ) {
				for ( let k = j + 1; k < filter.terms[ fieldName ].length; ++k ) {
					if ( filter.terms[ fieldName ][ j ] === filter.terms[ fieldName ][ k ] ) {
						filter.terms[ fieldName ].splice( k--, 1 );
					}
				}
			}
			appededExistingFilter = true;
		}
	}

	if ( !appededExistingFilter ) {
		const newFilter = { terms: {} };
		newFilter.terms[ fieldName ] = value;
		this.query.bool.filter.push( newFilter );
	}

	return this;
};

/**
 * Add term filter(s) for given field and value(s), another filter
 * for each value
 *
 * @param {string} field
 * @param {string} value
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addTermFilter = function ( field, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	for ( let valueIdx = 0; valueIdx < value.length; valueIdx++ ) {
		let exists = false;
		for ( let idx = 0; idx < this.query.bool.filter.length; idx++ ) {
			const filter = this.query.bool.filter[ idx ];
			if ( filter.term && filter.term[ field ] && filter.term[ field ] == value[ valueIdx ] ) { // eslint-disable-line eqeqeq
				exists = true;
				break;
			}
		}
		if ( exists ) {
			continue;
		}

		const newFilter = { term: {} };
		newFilter.term[ field ] = value[ valueIdx ];
		this.query.bool.filter.push( newFilter );
	}

	return this;
};

/**
 * Convinience function removing all filters for given field
 *
 * @param {string} field
 * @param {string | Array} value
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeFilter = function ( field, value ) {
	this.removeTermsFilter( field, value );
	this.removeTermFilter( field, value );
	return this;
};

/**
 * @param {string} fieldName
 * @param {string | Array} value
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeTermsFilter = function ( fieldName, value ) {
	return this.removeTerms( 'filter', fieldName, value );
};

bs.extendedSearch.Lookup.prototype.removeTerms = function ( prop, fieldName, value ) {
	this.ensurePropertyPath( 'query.bool.' + prop, [] );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	const newValues = [];
	for ( let i = 0; i < this.query.bool[ prop ].length; i++ ) {
		const item = this.query.bool[ prop ][ i ];
		const diffValues = [];

		// Not a terms filter - dont touch
		if ( !item.terms ) {
			newValues.push( item );
			continue;
		}

		if ( fieldName in item.terms ) {
			const oldValues = item.terms[ fieldName ];
			$.grep( oldValues, ( el ) => { // eslint-disable-line no-jquery/no-grep
				if ( $.inArray( el, value ) === -1 ) { // eslint-disable-line no-jquery/no-in-array
					diffValues.push( el );
				}
			} );

			if ( diffValues.length === 0 ) {
				continue;
			}

			item.terms[ fieldName ] = diffValues;
		}

		newValues.push( item );
	}

	this.query.bool[ prop ] = newValues;

	return this;
};

bs.extendedSearch.Lookup.prototype.removeTermFilter = function ( field, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	for ( let valueIdx = 0; valueIdx < value.length; valueIdx++ ) {
		for ( let idx = 0; idx < this.query.bool.filter.length; idx++ ) {
			const filter = this.query.bool.filter[ idx ];
			if ( filter.term && filter.term[ field ] && filter.term[ field ] == value[ valueIdx ] ) { // eslint-disable-line eqeqeq
				this.query.bool.filter.splice( idx, 1 );
			}
		}
	}

	return this;
};

/**
 * Example for complex sort
 *
 * "sort" : [
 *     { "post_date" : {"order" : "asc"}},
 *     "user",
 *     { "name" : "desc" },
 *     { "age" : "desc" },
 *     "_score"
 * ]
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
 *
 * @param {string} fieldName
 * @param {string|object} order
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addSort = function ( fieldName, order ) {
	this.ensurePropertyPath( 'sort', [] );
	order = order || bs.extendedSearch.Lookup.SORT_ASC;

	if ( typeof order === 'string' ) {
		order = {
			order: order
		};
	}

	let replacedExistingSort = false;
	for ( let i = 0; i < this.sort.length; i++ ) {
		const sorter = this.sort[ i ];
		if ( fieldName in sorter ) {
			sorter[ fieldName ] = order;
			replacedExistingSort = true;
		}
	}

	if ( !replacedExistingSort ) {
		const newSort = {};
		newSort[ fieldName ] = order;
		this.sort.push( newSort );
	}

	return this;
};

/*
 * @param {string} fieldName
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.removeSort = function ( fieldName ) {
	this.ensurePropertyPath( 'sort', [] );

	if ( !fieldName ) {
		this.sort = [];
		return this;
	}

	const newSort = [];
	for ( let i = 0; i < this.sort.length; i++ ) {
		const sorter = this.sort[ i ];
		if ( fieldName in sorter ) {
			continue;
		}
		newSort.push( sorter );
	}

	this.sort = newSort;

	if ( this.sort.length === 0 ) {
		delete ( this.sort );
	}

	return this;
};

/**
 * @return {Array}
 */
bs.extendedSearch.Lookup.prototype.getSort = function () {
	this.ensurePropertyPath( 'sort', [] );

	return this.sort;
};

/**
 * @param {Array} sort
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.setSort = function ( sort ) {
	this.sort = sort;
	return this;
};

/**
 * @param {string} field
 * @param {string|Array} value
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addShould = function ( field, value ) {
	return this.addShouldTerms( field, value );
};

/**
 * @param {string} field
 * @param {string|Array} value
 * @param {number} boost
 * @param {boolean} append
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addShouldTerms = function ( field, value, boost, append ) {
	this.ensurePropertyPath( 'query.bool.should', [] );

	boost = boost || 1;
	append = append !== false;

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	let appended = false;
	if ( append ) {
		for ( let shouldIdx = 0; shouldIdx < this.query.bool.should.length; shouldIdx++ ) {
			const should = this.query.bool.should[ shouldIdx ];
			if ( !( field in should.terms ) ) {
				continue;
			}
			this.query.bool.should[ shouldIdx ].terms[ field ] = $.merge(
				should.terms[ field ],
				value
			);
			appended = true;
		}
	}

	if ( !appended ) {
		const terms = { terms: { boost: boost } };
		terms.terms[ field ] = value;
		this.query.bool.should.push( terms );
	}

	return this;
};

/**
 * Adds should match clause.
 *
 * @param {string} field
 * @param {string} value
 * @param {number} boost
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addShouldMatch = function ( field, value, boost ) {
	this.ensurePropertyPath( 'query.bool.should', [] );
	boost = boost || 1;

	for ( let shouldIdx = 0; shouldIdx < this.query.bool.should.length; shouldIdx++ ) {
		const should = this.query.bool.should[ shouldIdx ];
		if ( !should.match || !should.match[ field ] ) {
			continue;
		}
		this.query.bool.should[ shouldIdx ].match[ field ] = {
			query: value,
			boost: boost
		};
		return this;
	}

	const match = { match: {} };
	match.match[ field ] = {
		query: value,
		boost: boost
	};
	this.query.bool.should.push( match );

	return this;
};

bs.extendedSearch.Lookup.prototype.removeShould = function ( field, value ) {
	return this.removeShouldTerms( field, value );
};
/**
 * @param {string} field
 * @param {string|Array} value
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeShouldTerms = function ( field, value ) {
	this.ensurePropertyPath( 'query.bool.should', [] );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	for ( let shouldIdx = 0; shouldIdx < this.query.bool.should.length; shouldIdx++ ) {
		const should = this.query.bool.should[ shouldIdx ];
		if ( !should.terms || !should.terms[ field ] ) {
			continue;
		}
		const oldValues = should.terms[ field ];
		const newValues = [];
		$.grep( oldValues, ( el ) => { // eslint-disable-line no-jquery/no-grep
			if ( $.inArray( el, value ) === -1 ) { // eslint-disable-line no-jquery/no-in-array
				newValues.push( el );
			}
		} );

		if ( newValues.length === 0 || value.length === 0 ) {
			this.query.bool.should.splice( shouldIdx, 1 );
			continue;
		}
		this.query.bool.should[ shouldIdx ].terms[ field ] = newValues;
	}

	return this;
};

/**
 * @param {string} field
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeShouldMatch = function ( field ) {
	this.ensurePropertyPath( 'query.bool.should', [] );

	const newShoulds = [];
	for ( let shouldIdx = 0; shouldIdx < this.query.bool.should.length; shouldIdx++ ) {
		const should = this.query.bool.should[ shouldIdx ];
		if ( !should.match || !should.match[ field ] ) {
			newShoulds.push( should );
		}
	}

	this.query.bool.should = newShoulds;

	return this;
};

/**
 * @return {Array}
 */
bs.extendedSearch.Lookup.prototype.getShould = function () {
	this.ensurePropertyPath( 'query.bool.should', [] );
	return this.query.bool.should;
};

/**
 * Removes all methods and stuff from current object to provide an easy-to-use
 * object that can be fed directly into the search backend
 *
 * @return {Object}
 */
bs.extendedSearch.Lookup.prototype.getQueryDSL = function () {
	return JSON.parse( JSON.stringify( this ) );
};

bs.extendedSearch.Lookup.prototype.addHighlighter = function ( field ) {
	this.ensurePropertyPath( 'highlight.fields', {} );

	this.highlight.fields[ field ] = {
		matched_fields: field,
		pre_tags: [ '<b>' ],
		post_tags: [ '</b>' ]
	};

	return this;
};

bs.extendedSearch.Lookup.prototype.removeHighlighter = function ( field ) {
	this.ensurePropertyPath( 'highlight.fields', {} );

	if ( field in this.highlight.fields ) {
		delete ( this.highlight.fields[ field ] );
	}

	if ( $.isEmptyObject( this.highlight.fields ) ) {
		delete ( this.highlight );
	}

	return this;
};

bs.extendedSearch.Lookup.prototype.setSize = function ( size ) {
	this.ensurePropertyPath( 'size', 0 );
	this.size = size;

	return this;
};

bs.extendedSearch.Lookup.prototype.getSize = function () {
	this.ensurePropertyPath( 'size', 0 );
	return this.size;
};

/**
 * Adds a field or fields to the set of fields which
 * will be returned in the _source key in result
 *
 * @param {string | Array} field
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.addSourceField = function ( field ) {
	this.ensurePropertyPath( '_source', [] );

	if ( !Array.isArray( field ) ) {
		field = [ field ];
	}
	this._source = $.merge( this._source, field );

	return this;
};

/**
 * Removes field/fields from _source param
 *
 * @param {string | Array} field
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.removeSourceField = function ( field ) {
	this.ensurePropertyPath( '_source', [] );

	if ( !Array.isArray( field ) ) {
		field = [ field ];
	}

	const newSource = [];
	for ( let i = 0; i < this._source.length; i++ ) {
		const sourceField = this._source[ i ];
		if ( $.inArray( sourceField, field ) !== -1 ) { // eslint-disable-line no-jquery/no-in-array
			continue;
		}
		newSource.push( sourceField );
	}

	if ( newSource.length === 0 ) {
		delete ( this._source );
	} else {
		this._source = newSource;
	}

	return this;
};

/**
 * Completely removed _source key, meaning all available fields
 * will be returned
 *
 * @return {bs.extendedSearch.Lookup}
 */
bs.extendedSearch.Lookup.prototype.clearSourceField = function () {
	this.ensurePropertyPath( '_source', [] );

	delete ( this._source );

	return this;
};

bs.extendedSearch.Lookup.prototype.setFrom = function ( from ) {
	this.ensurePropertyPath( 'from', 0 );
	this.from = from;

	return this;
};

bs.extendedSearch.Lookup.prototype.setSearchAfter = function ( values ) {
	this.ensurePropertyPath( 'search_after', [] );

	if ( !Array.isArray( values ) ) {
		values = [ values ];
	}

	// From and search_after cannot coexist in the same query
	this.from = 0;

	this.search_after = values;

	return this;
};

bs.extendedSearch.Lookup.prototype.removeSearchAfter = function () {
	this.ensurePropertyPath( 'search_after', [] );

	delete ( this.search_after );

	return this;
};

bs.extendedSearch.Lookup.prototype.getFrom = function () {
	this.ensurePropertyPath( 'from', 0 );
	return this.from;
};

bs.extendedSearch.Lookup.prototype.addAutocompleteSuggest = function ( field, value, suggestName ) {
	this.ensurePropertyPath( 'suggest', {} );

	suggestName = suggestName || field;

	this.suggest[ suggestName ] = {
		prefix: value,
		completion: {
			field: field
		}
	};

	return this;
};

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggest = function ( suggestName ) {
	this.ensurePropertyPath( 'suggest', {} );

	const newSuggest = {};
	for ( const field in this.suggest ) {
		if ( !this.suggest.hasOwnProperty( field ) ) {
			continue;
		}
		if ( field === suggestName ) {
			continue;
		}

		newSuggest[ field ] = this.suggest[ field ];
	}

	this.suggest = newSuggest;

	if ( this.suggest.length === 0 ) {
		delete ( this.suggest );
	}

	return this;
};

bs.extendedSearch.Lookup.prototype.getAutocompleteSuggest = function () {
	this.ensurePropertyPath( 'suggest', {} );

	return this.suggest;
};

bs.extendedSearch.Lookup.prototype.addAutocompleteSuggestContext = function ( acField, contextField, value ) {
	this.ensurePropertyPath( 'suggest', {} );

	if ( !Array.isArray( value ) ) {
		value = [ value ];
	}

	if ( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts', {} );

	this.suggest[ acField ].completion.contexts[ contextField ] = value;

	return this;
};

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggestContext = function ( acField, contextField ) {
	this.ensurePropertyPath( 'suggest', {} );

	if ( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts.' + contextField, [] );

	delete ( this.suggest[ acField ].completion.contexts[ contextField ] );

	if ( $.isEmptyObject( this.suggest[ acField ].completion.contexts ) ) {
		delete ( this.suggest[ acField ].completion.contexts );
	}

	return this;
};

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggestContextValue = function ( acField, contextField, value ) {
	value = value || false;

	this.ensurePropertyPath( 'suggest', {} );

	if ( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts.' + contextField, [] );

	const newValues = [];
	for ( let i = 0; i < this.suggest[ acField ].completion.contexts[ contextField ].length; i++ ) {
		const contextValue = this.suggest[ acField ].completion.contexts[ contextField ][ i ];
		if ( contextValue !== value ) {
			newValues.push( contextValue );
		}
	}

	if ( newValues.length === 0 ) {
		delete ( this.suggest[ acField ].completion.contexts[ contextField ] );
		if ( $.isEmptyObject( this.suggest[ acField ].completion.contexts ) ) {
			delete ( this.suggest[ acField ].completion.contexts );
		}
		return;
	}

	this.suggest[ acField ].completion.contexts[ contextField ] = newValues;

	return this;
};

bs.extendedSearch.Lookup.prototype.addAutocompleteSuggestFuzziness = function ( acField, fuzzinessLevel ) {
	this.ensurePropertyPath( 'suggest', {} );

	if ( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.fuzzy', {} );

	this.suggest[ acField ].completion.fuzzy = { fuzziness: fuzzinessLevel };

	return this;
};

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggestFuzziness = function ( acField ) {
	this.ensurePropertyPath( 'suggest', {} );

	if ( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.fuzzy', [] );

	delete ( this.suggest[ acField ].completion.fuzzy );

	return this;
};

bs.extendedSearch.Lookup.prototype.setAutocompleteSuggestSize = function ( acField, size ) {
	this.ensurePropertyPath( 'suggest', {} );

	if ( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion', {} );

	this.suggest[ acField ].completion.size = size;

	return this;
};

bs.extendedSearch.Lookup.prototype.setForceTerm = function () {
	this.forceTerm = true;
	return this;
};

bs.extendedSearch.Lookup.prototype.removeForceTerm = function () {
	delete ( this.forceTerm );
	return this;
};

bs.extendedSearch.Lookup.prototype.getForceTerm = function () {
	if ( 'forceTerm' in this ) {
		return true;
	}

	return false;
};

bs.extendedSearch.Lookup.prototype.getContext = function () {
	return this.context || null;
};

bs.extendedSearch.Lookup.prototype.setContext = function ( context ) {
	if ( !context ) {
		if ( this.context ) {
			delete this.context;
		}
		return;
	}
	this.context = context;
};
