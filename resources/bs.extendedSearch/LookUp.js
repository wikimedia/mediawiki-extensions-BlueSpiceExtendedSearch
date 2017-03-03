bs.extendedSearch.LookUp = function( config ) {
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
OO.initClass( bs.extendedSearch.LookUp );

bs.extendedSearch.LookUp.SORT_ASC = 'asc';
bs.extendedSearch.LookUp.SORT_DESC = 'desc';

/**
 *
 * @private
 * @param string path
 * @param mixed initialValue
 * @param object initialValue
 * @returns void
 */
bs.extendedSearch.LookUp.prototype.ensurePropertyPath = function ( path, initialValue, base ) {
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
 * @param string type
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.setType = function ( type ) {
	this.ensurePropertyPath( 'query.type.value', '' );
	this.query.type.value = type;
	return this;
};

/**
 *
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.clearType = function () {
	this.ensurePropertyPath( 'query.type.value', '' );
	delete( this.query.type );
	return this;
};

/**
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.x/query-dsl-simple-query-string-query.html
 * @param string|object q
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.setSimpleQueryString = function ( q ) {
	this.ensurePropertyPath( 'query.simple_query_string', {} );
	if( typeof q === 'object' ) {
		this.query.simple_query_string = q;
	}
	if( typeof q === 'string' ) {
		this.query.simple_query_string = {
			query: q,
			default_operator: 'and'
		};
	}
	return this;
};

/**
 *
 * @param string|object q
 * @returns string|object|null
 */
bs.extendedSearch.LookUp.prototype.getSimpleQueryString = function () {
	if( !this.query || !this.query.simple_query_string ) {
		return null;
	}
	return this.query.simple_query_string;
};

/**
 *
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.clearSimpleQueryString = function () {
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
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.addFilter = function( fieldName, value ) {
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
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.removeFilter = function( fieldName, value ) {
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
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.addSort = function( fieldName, order ) {
	this.ensurePropertyPath( 'sort', [] );
	order = order || bs.extendedSearch.LookUp.SORT_ASC;

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
 * @returns bs.extendedSearch.LookUp
 */
bs.extendedSearch.LookUp.prototype.removeSort = function( fieldName ) {
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

/**
 * Removes all methods and stuff from current object to provide an easy-to-use
 * object that can be fed directly into the search backend
 * @returns object
 */
bs.extendedSearch.LookUp.prototype.getQueryDSL = function() {
	return JSON.parse( JSON.stringify( this ) );
};