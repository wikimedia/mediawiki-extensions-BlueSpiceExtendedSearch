bs.extendedSearch.Lookup = function( config ) {
	for( var field in config ) {
		if( $.isFunction( config[field] ) ) {
			continue;
		}

		if( this[field] ) {
			continue;
		}

		this[field] = config[field];
	}
};
OO.initClass( bs.extendedSearch.Lookup );

bs.extendedSearch.Lookup.SORT_ASC = 'asc';
bs.extendedSearch.Lookup.SORT_DESC = 'desc';
bs.extendedSearch.Lookup.TYPE_FIELD_NAME = '_type';

/**
 *
 * @private
 * @param string path
 * @param mixed initialValue
 * @param object initialValue
 * @returns void
 */
bs.extendedSearch.Lookup.prototype.ensurePropertyPath = function ( path, initialValue, base ) {
	base = base || this;
	var pathParts = path.split( '.' );
	if( !( !base[pathParts[0]] && pathParts.length === 1 ) ) {
		base[pathParts[0]] = base[pathParts[0]] || {};
		base = base[pathParts[0]];
		pathParts.shift(); //Remove first element
		if( pathParts.length > 0 ) {
			this.ensurePropertyPath( pathParts.join('.'), initialValue, base );
		}
	}
	else {
		base[pathParts[0]] = initialValue;
	}
};

/**
 *
 * @param string field
 * @param string q
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.setMatchQueryString = function( field, q ) {
	this.ensurePropertyPath( 'query.match', {} );
	this.query.match[field] = { query: q };

	return this;
}

/**
 *
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.removeMatchQuery = function() {
	this.ensurePropertyPath( 'query.match', {} );
	delete( this.query.match );

	return this;
}

/**
 *
 * @param string field
 * @param int fuzziness
 * @param array|null options
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.setBoolMatchQueryFuzziness = function( field, fuzziness, options ) {
	options = options || {};

	options.fuzziness = fuzziness;

	this.ensurePropertyPath( 'query.bool.must.match.' + field, {} );
	this.query.bool.must.match[field] = $.extend( this.query.bool.must.match[field], options );

	return this;
}

/**
 * Sets match query string
 *
 * @param string field
 * @param string q
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.setBoolMatchQueryString = function( field, q ) {
	this.ensurePropertyPath( 'query.bool', {} );

	var must = { match: {} };
	must.match[field] = { query: q };

	this.query.bool.must = must;

	return this;
}

/**
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.x/query-dsl-query-string-query.html
 * @param string|object q
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.setQueryString = function ( q ) {
	this.ensurePropertyPath( 'query.bool.must', [] );
	var newMusts = [];

	//There must not be more than on "query_string" in "must"
	for( var i = 0; i < this.query.bool.must.length; i++ ) {
		if( 'query_string' in this.query.bool.must[i] ) {
			continue;
		}
		newMusts.push( this.query.bool.must[i] );
	}

	this.query.bool.must = newMusts;

	if( typeof q === 'object' ) {
		this.query.bool.must.push( {
			query_string: q
		});
	}
	if( typeof q === 'string' ) {
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
 *
 * @returns object|null
 */
bs.extendedSearch.Lookup.prototype.getQueryString = function () {
	this.ensurePropertyPath( 'query.bool.must', [] );

	for( var i = 0; i < this.query.bool.must.length; i++ ) {
		if( 'query_string' in this.query.bool.must[i] ) {
			return this.query.bool.must[i].query_string;
		}
	}

	return '';
};

/**
 * Warning: Use carefully! Removing "must" containing
 * query_string will not stop the query from being executed
 * with other parts of bool query, may lead to unexpected results
 *
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.clearQueryString = function () {
	this.ensurePropertyPath( 'query.bool.must', {} );

	for( mustIdx in this.query.bool.must ) {
		var must = this.query.bool.must[mustIdx];

		if( 'query_string' in must ) {
			this.query.bool.must.splice( mustIdx, 1 );
		}
	}
	return this;
};

bs.extendedSearch.Lookup.prototype.addBoolMustNotTerms = function( field, value ) {
	this.ensurePropertyPath( 'query.bool.must_not', [] );

	if( !$.isArray( value ) ) {
		value = [value];
	}

	for( idx in this.query.bool.must_not ) {
		var terms = this.query.bool.must_not[idx];
		if( terms.terms[field] ) {
			this.query.bool.must_not[idx].terms[field] = $.merge(
				terms.terms[field],
				value
			);
			return this;
		}
	}

	var newMustNot = { terms: {} };
	newMustNot.terms[field] = value;
	this.query.bool.must_not.push( newMustNot );

	return this;
}

/**
 * Removes field from must_not clause
 *
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.removeBoolMustNot = function( field ) {
	this.ensurePropertyPath( 'query.bool.must_not', [] );

	var newMustNots = [];
	for( idx in this.query.bool.must_not ) {
		var terms = this.query.bool.must_not[idx];
		for( fieldName in terms.terms ) {
			if( fieldName == field ) {
				continue;
			}
			newMustNots.push( terms );
		}
	}

	if( newMustNots.length == 0 ) {
		delete( this.query.bool.must_not );
	} else {
		this.query.bool.must_not = newMustNots;
	}

	return this;
}

/**
 * Removes filter completely regardless of value
 *
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.clearFilter = function ( field ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	var newFilters = [];
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		var filter = this.query.bool.filter[i]
		if( filter.terms && field in this.query.bool.filter[i].terms ) {
			continue;
		}
		if( filter.term && field in filter.term ) {
			continue;
		}
		newFilters.push( this.query.bool.filter[i] );
	}

	delete( this.query.bool.filter );
	if( newFilters.length > 0 ) {
		this.query.bool.filter = newFilters;
	}

	return this;
};

/**
 * Gets all filters in lookup in form:
 * {
 *		terms: {
 *			field1: [values],
 *			field2: [values]
 *		},
 *		term: {
 *			field1: [values],
 *			field2: [values]
 *		}
 * }
 * @returns Object
 */
bs.extendedSearch.Lookup.prototype.getFilters = function () {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	var filters = {};
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		var filter = this.query.bool.filter[i];

		for( typeName in filter ) {
			if( !filters[typeName] ) {
				filters[typeName] = {};
			}
			for( fieldName in filter[typeName] ) {
				if( !filters[typeName][fieldName] ) {
					filters[typeName][fieldName] = [];
				}
				var filterValue = filter[typeName][fieldName];
				if( $.isArray( filterValue ) ) {
					$.merge( filters[typeName][fieldName], filterValue );
				} else {
					filters[typeName][fieldName].push( filterValue );
				}
			}
		}
	}

	return filters;
};

/**
 * Example for complex filter
 *
 * "query": {
 *       "bool": {
 *           "filter": [{
 *               "terms": { "entitydata.parentid": [ 0 ] }
 *           },{
 *               "terms": { "entitydata.type": [ "microblog", "profile" ] }
 *           }]
 *       }
 *   }
 * @param string fieldName
 * @param string|array value
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.addTermsFilter = function( fieldName, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if( !$.isArray( value ) ) {
		value = [ value ];
	}

	//HINT: "[terms] query does not support multiple fields" - Therefore we
	//need to make a dedicated { "terms" } object for each field
	var appededExistingFilter = false;
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		var filter = this.query.bool.filter[i];

		//Append
		if( filter.terms && fieldName in filter.terms ) {
			filter.terms[fieldName] = filter.terms[fieldName].concat( value );

			//Clean up duplicates: http://stackoverflow.com/questions/1584370/how-to-merge-two-arrays-in-javascript-and-de-duplicate-items
			for( var j = 0 ; j < filter.terms[fieldName].length; ++j ) {
				for(var k = j + 1; k < filter.terms[fieldName].length; ++k ) {
					if( filter.terms[fieldName][j] === filter.terms[fieldName][k] )
						filter.terms[fieldName].splice( k--, 1 );
				}
			}
			appededExistingFilter = true;
		}
	}

	if( !appededExistingFilter ) {
		var newFilter = { terms: {} };
		newFilter.terms[fieldName] = value;
		this.query.bool.filter.push( newFilter );
	}

	return this;
};

/**
 * Add term filter(s) for given field and value(s), another filter
 * for each value
 *
 * @param string field
 * @param string value
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.addTermFilter = function( field, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if( !$.isArray( value ) ) {
		value = [ value ];
	}

	for( valueIdx in value ) {
		var exists = false;
		for( idx in this.query.bool.filter ) {
			var filter = this.query.bool.filter[idx];
			if( filter.term && filter.term[field] && filter.term[field] == value[valueIdx] ) {
				exists = true;
				break;
			}
		}
		if( exists ) {
			continue;
		}

		var newFilter = { term: {} };
		newFilter.term[field] = value[valueIdx];
		this.query.bool.filter.push( newFilter );
	}

	return this;
}

/**
 * Convinience function removing all filters for given field
 *
 * @param string field
 * @param string|array value
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.removeFilter = function( field, value ) {
	this.removeTermsFilter( field, value );
	this.removeTermFilter( field, value );
	return this;
}

/**
 *
 * @param string fieldName
 * @param string|array value
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.removeTermsFilter = function( fieldName, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if( !$.isArray( value ) ) {
		value = [ value ];
	}

	var newFilters = [];
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		var filter = this.query.bool.filter[i];
		var diffValues = [];

		//Not a terms filter - dont touch
		if( !filter.terms ) {
			newFilters.push( filter );
			continue;
		}

		if( fieldName in filter.terms ) {
			var oldValues = filter.terms[fieldName];
			$.grep( oldValues, function( el ) {
				if ( $.inArray( el, value ) === -1 ) {
					diffValues.push( el );
				}
			});

			if( diffValues.length === 0 ) {
				continue;
			}

			filter.terms[fieldName] = diffValues;
		}

		newFilters.push( filter );
	}

	this.query.bool.filter = newFilters;

	return this;
};

bs.extendedSearch.Lookup.prototype.removeTermFilter = function( field, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if( !$.isArray( value ) ) {
		value = [ value ];
	}

	for( valueIdx in value ) {
		for( idx in this.query.bool.filter ) {
			var filter = this.query.bool.filter[idx];
			if( filter.term && filter.term[field] && filter.term[field] == value[valueIdx] ) {
				this.query.bool.filter.splice( idx, 1 );
			}
		}
	}

	return this;
}

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
 * @param string fieldName
 * @param string|object order
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.addSort = function( fieldName, order ) {
	this.ensurePropertyPath( 'sort', [] );
	order = order || bs.extendedSearch.Lookup.SORT_ASC;

	if( typeof order === 'string' ) {
		order = {
			"order": order
		};
	}

	var replacedExistingSort = false;
	for( var i = 0; i < this.sort.length; i++ ) {
		var sorter = this.sort[i];
		if( fieldName in sorter ) {
			sorter[fieldName] = order;
			replacedExistingSort = true;
		}
	}

	if( !replacedExistingSort ) {
		var newSort = {};
		newSort[fieldName] = order;
		this.sort.push( newSort );
	}

	return this;
};

/*
 * @param string fieldName
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.removeSort = function( fieldName ) {
	this.ensurePropertyPath( 'sort', [] );

	if( !fieldName ) {
		this.sort = [];
		return this;
	}

	var newSort = [];
	for( var i = 0; i < this.sort.length; i++ ) {
		var sorter = this.sort[i];
		if( fieldName in sorter ) {
			continue;
		}
		newSort.push( sorter );
	}

	this.sort = newSort;

	if( this.sort.length === 0 ) {
		delete( this.sort );
	}

	return this;
};

/*
 * @param string fieldName
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.getSort = function() {
	this.ensurePropertyPath( 'sort', [] );

	return this.sort;
};

/**
 *
 * @param Arry sort
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.setSort = function( sort ) {
	this.sort = sort;
	return this;
}

/**
 *
 * @param string field
 * @param string|Array value
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.addShould = function( field, value ) {
	return this.addShouldTerms( field, value );
}

/**
 *
 * @param string field
 * @param string|Array value
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.addShouldTerms = function( field, value, boost, append ) {
	this.ensurePropertyPath( 'query.bool.should', [] );

	boost = boost || 1;
	append = append === false ? false : true;

	if( !$.isArray( value ) ) {
		value = [value];
	}

	var appended = false;
	if( append ) {
		for( shouldIdx in this.query.bool.should ) {
			var should = this.query.bool.should[shouldIdx];
			if( !( field in should.terms ) ) {
				continue;
			}
			this.query.bool.should[shouldIdx].terms[field] = $.merge(
				should.terms[field],
				value
			);
			appended = true;
		}
	}

	if( !appended ) {
		var terms = { terms: { boost: boost } };
		terms.terms[field] = value;
		this.query.bool.should.push( terms );
	}

	return this;
}

/**
 * Adds should match clause.
 *
 * @param string field
 * @param string value
 * @param int boost
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.addShouldMatch = function( field, value, boost ) {
	this.ensurePropertyPath( 'query.bool.should', [] );
	boost = boost || 1;

	for( shouldIdx in this.query.bool.should ) {
		var should = this.query.bool.should[shouldIdx];
		if( !should.match || !should.match[field] ) {
			continue;
		}
		this.query.bool.should[shouldIdx].match[field] = {
			query: value,
			boost: boost
		};
		return this;
	}

	var match = { match: {} };
	match.match[field] = {
		query: value,
		boost: boost
	}
	this.query.bool.should.push( match );

	return this;
}

bs.extendedSearch.Lookup.prototype.removeShould = function( field, value ) {
	return this.removeShouldTerms( field, value );
}
/**
 *
 * @param string field
 * @param string|Array value
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.removeShouldTerms = function( field, value ) {
	this.ensurePropertyPath( 'query.bool.should', [] );

	if( !$.isArray( value ) ) {
		value = [value];
	}

	for( shouldIdx in this.query.bool.should ) {
		var should = this.query.bool.should[shouldIdx];
		if( !should.terms || !should.terms[field] ) {
			continue;
		}
		var oldValues = should.terms[field];
		var newValues = [];
		$.grep( oldValues, function( el ) {
			if ( $.inArray( el, value ) === -1 ) {
				newValues.push( el );
			}
		} );

		if( newValues.length === 0 || value.length === 0 ) {
			this.query.bool.should.splice( shouldIdx, 1 );
			continue;
		}
		this.query.bool.should[shouldIdx].terms[field] = newValues;
	}

	return this;
}

/**
 *
 * @param string field
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.removeShouldMatch = function( field ) {
	this.ensurePropertyPath( 'query.bool.should', [] );

	var newShoulds = [];
	for( shouldIdx in this.query.bool.should ) {
		var should = this.query.bool.should[shouldIdx];
		if( !should.match || !should.match[field] ) {
			newShoulds.push( should );
		}
	}

	this.query.bool.should = newShoulds;

	return this;
}

/**
 *
 * @returns Array
 */
bs.extendedSearch.Lookup.prototype.getShould = function() {
	this.ensurePropertyPath( 'query.bool.should', [] );
	return this.query.bool.should;
}

/**
 * Removes all methods and stuff from current object to provide an easy-to-use
 * object that can be fed directly into the search backend
 * @returns object
 */
bs.extendedSearch.Lookup.prototype.getQueryDSL = function() {
	return JSON.parse( JSON.stringify( this ) );
};

bs.extendedSearch.Lookup.prototype.addHighlighter = function( field ) {
	this.ensurePropertyPath( 'highlight.fields', {} );

	this.highlight.fields[field] = {
		matched_fields: field,
		pre_tags: [ "<b>" ],
		post_tags: [ "</b>" ]
	}

	return this;
}

bs.extendedSearch.Lookup.prototype.removeHighlighter = function ( field ) {
	this.ensurePropertyPath( 'highlight.fields', {} );

	if( field in this.highlight.fields ) {
		delete( this.highlight.fields[field] );
	}

	if( $.isEmptyObject( this.highlight.fields ) ) {
		delete( this.highlight );
	}

	return this;
}

bs.extendedSearch.Lookup.prototype.setSize = function( size ) {
	this.ensurePropertyPath( 'size', 0 );
	this.size = size;

	return this;
}


bs.extendedSearch.Lookup.prototype.getSize = function() {
	this.ensurePropertyPath( 'size', 0 );
	return this.size;
}

/**
 * Adds a field or fields to the set of fields which
 * will be returned in the _source key in result
 *
 * @param string|array field
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.addSourceField = function( field ) {
	this.ensurePropertyPath( '_source', [] );

	if( !$.isArray( field ) ) {
		field = [field];
	}
	this._source = $.merge( this._source, field );

	return this;
}

/**
 * Removes field/fields from _source param
 *
 * @param string|array field
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.removeSourceField = function( field ) {
	this.ensurePropertyPath( '_source', [] );

	if( !$.isArray( field ) ) {
		field = [field];
	}

	var newSource = [];
	for( fieldIdx in this._source ) {
		var sourceField = this._source[fieldIdx];
		if( $.inArray( sourceField, field ) != -1 ) {
			continue;
		}
		newSource.push( sourceField );
	}

	if( newSource.length == 0 ) {
		delete( this._source );
	} else {
		this._source = newSource;
	}

	return this;
}

/**
 * Completely removed _source key, meaning all available fields
 * will be returned
 *
 * @returns Lookup
 */
bs.extendedSearch.Lookup.prototype.clearSourceField = function() {
	this.ensurePropertyPath( '_source', [] );

	delete( this._source );

	return this;
}

bs.extendedSearch.Lookup.prototype.setFrom = function( from ) {
	this.ensurePropertyPath( 'from', 0 );
	this.from = from;

	return this;
}

bs.extendedSearch.Lookup.prototype.setSearchAfter = function( values ) {
	this.ensurePropertyPath( 'search_after', [] );

	if( $.isArray( values ) == false ) {
		values = [values];
	}

	//From and search_after cannot coexist in the same query
	delete( this.from );

	this.search_after = values;

	return this;
}

bs.extendedSearch.Lookup.prototype.removeSearchAfter = function() {
	this.ensurePropertyPath( 'search_after', [] );

	delete( this.search_after );

	return this;
}

bs.extendedSearch.Lookup.prototype.getFrom = function() {
	this.ensurePropertyPath( 'from', 0 );
	return this.from;
}

bs.extendedSearch.Lookup.prototype.addAutocompleteSuggest = function( field, value, suggestName ) {
	this.ensurePropertyPath( 'suggest', {} );

	suggestName = suggestName || field;

	this.suggest[suggestName] = {
		prefix: value,
		completion: {
			field: field
		}
	};

	return this;
}

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggest = function( suggestName ) {
	this.ensurePropertyPath( 'suggest', {} );

	var newSuggest = {};
	for( field in this.suggest ) {
		if( field === suggestName ) {
			continue;
		}

		newSuggest[field] = this.suggest[field];
	}

	this.suggest = newSuggest;

	if( this.suggest.length === 0 ) {
		delete( this.suggest );
	}

	return this;
}

bs.extendedSearch.Lookup.prototype.getAutocompleteSuggest = function() {
	this.ensurePropertyPath( 'suggest', {} );

	return this.suggest;
}

bs.extendedSearch.Lookup.prototype.addAutocompleteSuggestContext = function( acField, contextField, value ) {
	this.ensurePropertyPath( 'suggest', {} );

	if( $.isArray( value ) == false ) {
		value = [ value ];
	}

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts', {} );

	this.suggest[acField].completion.contexts[contextField] = value;

	return this;
}

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggestContext = function( acField, contextField ) {
	this.ensurePropertyPath( 'suggest', {} );

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts.' + contextField, [] );

	delete( this.suggest[acField]['completion']['contexts'][contextField] );

	if( $.isEmptyObject( this.suggest[acField]['completion']['contexts'] ) ) {
		delete( this.suggest[acField]['completion']['contexts'] );
	}

	return this;
}

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggestContextValue = function( acField, contextField, value ) {
	value = value || false;

	this.ensurePropertyPath( 'suggest', {} );

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts.' + contextField, [] );

	var newValues = [];
	for( idx in this.suggest[acField]['completion']['contexts'][contextField] ) {
		var contextValue = this.suggest[acField]['completion']['contexts'][contextField][idx];
		if( contextValue != value ) {
			newValues.push( contextValue );
		}
	}

	if( newValues.length === 0 ) {
		delete( this.suggest[acField]['completion']['contexts'][contextField] );
		if( $.isEmptyObject( this.suggest[acField]['completion']['contexts'] ) ) {
			delete( this.suggest[acField]['completion']['contexts'] );
		}
		return;
	}

	this.suggest[acField]['completion']['contexts'][contextField] = newValues;

	return this;
}

bs.extendedSearch.Lookup.prototype.addAutocompleteSuggestFuzziness = function( acField, fuzzinessLevel ) {
	this.ensurePropertyPath( 'suggest', {} );

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.fuzzy', {} );

	this.suggest[acField].completion.fuzzy = { fuzziness: fuzzinessLevel };

	return this;
}

bs.extendedSearch.Lookup.prototype.removeAutocompleteSuggestFuzziness = function( acField ) {
	this.ensurePropertyPath( 'suggest', {} );

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.fuzzy', [] );

	delete( this.suggest[acField].completion.fuzzy );

	return this;
}

bs.extendedSearch.Lookup.prototype.setAutocompleteSuggestSize = function( acField, size ) {
	this.ensurePropertyPath( 'suggest', {} );

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion', {} );

	this.suggest[acField].completion.size = size;

	return this;
}

bs.extendedSearch.Lookup.prototype.setForceTerm = function() {
	this.forceTerm = true;
	return this;
}

bs.extendedSearch.Lookup.prototype.removeForceTerm = function() {
	delete( this.forceTerm );
	return this;
}

bs.extendedSearch.Lookup.prototype.getForceTerm = function() {
	if( 'forceTerm' in this ) {
		return true;
	}

	return false;
}