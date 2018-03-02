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
 * @param [] type
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.setTypes = function ( types ) {
	this.clearTypes();
	this.addFilter( '_type', types );
	return this;
};

/**
 *
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.clearTypes = function () {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	var newFilter = [];
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		if( '_type' in this.query.bool.filter[i].terms ) {
			continue;
		}
		newFilter.push( this.query.bool.filter[i] );
	}

	delete( this.query.bool.filter );
	if( newFilter.length > 0 ) {
		this.query.bool.filter = newFilter;
	}

	return this;
};

/**
 *
 * @returns []
 */
bs.extendedSearch.Lookup.prototype.getTypes = function () {
	this.ensurePropertyPath( 'query.bool.filter', [] );
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		if( '_type' in this.query.bool.filter[i].terms ) {
			return this.query.bool.filter[i].terms['_type'];
		}
	}
	return [];
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
 *
 * @returns array
 */
bs.extendedSearch.Lookup.prototype.getFilters = function () {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	var filters = [];
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		if( 'terms' in this.query.bool.filter[i] ) {
			filters.push( this.query.bool.filter[i].terms );
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
bs.extendedSearch.Lookup.prototype.addFilter = function( fieldName, value ) {
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
 *
 * @param string fieldName
 * @param string|array value
 * @returns bs.extendedSearch.Lookup
 */
bs.extendedSearch.Lookup.prototype.removeFilter = function( fieldName, value ) {
	this.ensurePropertyPath( 'query.bool.filter', [] );

	if( !$.isArray( value ) ) {
		value = [ value ];
	}

	var newFilters = [];
	for( var i = 0; i < this.query.bool.filter.length; i++ ) {
		var filter = this.query.bool.filter[i];
		var diffValues = [];

		if( filter.terms && fieldName in filter.terms ) {
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