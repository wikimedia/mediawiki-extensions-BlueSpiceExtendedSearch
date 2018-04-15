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
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.x/query-dsl-simple-query-string-query.html
 * @param string|object q
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.setSimpleQueryString = function ( q ) {
	this.ensurePropertyPath( 'query.bool.must', [] );
	var newMusts = [];

	//There must not be more than on "simple_query_string" in "must"
	for( var i = 0; i < this.query.bool.must.length; i++ ) {
		if( 'simple_query_string' in this.query.bool.must[i] ) {
			continue;
		}
		newMusts.push( this.query.bool.must[i] );
	}

	this.query.bool.must = newMusts;

	if( typeof q === 'object' ) {
		this.query.bool.must.push( {
			simple_query_string: q
		});
	}
	if( typeof q === 'string' ) {
		this.query.bool.must.push( {
			simple_query_string: {
				query: q,
				default_operator: 'and'
			}
		} );
	}
	return this;
};

/**
 *
 * @returns object|null
 */
bs.extendedSearch.Lookup.prototype.getSimpleQueryString = function () {
	this.ensurePropertyPath( 'query.bool.must', [] );

	for( var i = 0; i < this.query.bool.must.length; i++ ) {
		if( 'simple_query_string' in this.query.bool.must[i] ) {
			return this.query.bool.must[i].simple_query_string;
		}
	}

	return null;
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
 *
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.clearSimpleQueryString = function () {
	this.ensurePropertyPath( 'query.simple_query_string', {} );
	delete( this.query.simple_query_string );
	return this;
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
 * Removes all methods and stuff from current object to provide an easy-to-use
 * object that can be fed directly into the search backend
 * @returns object
 */
bs.extendedSearch.Lookup.prototype.getQueryDSL = function() {
	return JSON.parse( JSON.stringify( this ) );
};

bs.extendedSearch.Lookup.prototype.addHighlighter = function( field ) {
	this.ensurePropertyPath( 'highlight.field', [] );

	this.highlight.field[field] = {
		matched_fields: field
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

bs.extendedSearch.Lookup.prototype.setFrom = function( from ) {
	this.ensurePropertyPath( 'from', 0 );
	this.from = from;

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
		if( fieldName === suggestName ) {
			continue;
		}

		newSuggest[fieldName] = this.suggest[fieldName];
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
	value = value || false;

	this.ensurePropertyPath( 'suggest', {} );

	if( !( acField in this.suggest ) ) {
		return;
	}

	this.ensurePropertyPath( 'suggest.' + acField + '.completion.contexts.' + contextField, [] );

	delete( this.suggest[acField]['completion']['contexts'][contextField] );

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

	this.suggest[acField]['completion']['contexts'][contextField] = newValues();

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
	value = value || false;

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