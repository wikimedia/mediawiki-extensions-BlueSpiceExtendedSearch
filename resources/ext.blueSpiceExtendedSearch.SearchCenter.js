( function( mw, $, bs, d, undefined ){
	/**
	 * Makes config object for special Type filter
	 * This filter contains different results types (one for each source)
	 * that can be filtered
	 *
	 * @returns {Array}
	 */
	function _getTypeFilter() {
		var availableTypes = mw.config.get( 'bsgESAvailbleTypes' );

		if( availableTypes.length === 0 ) {
			return [];
		}

		var typeFilter = {
			label: mw.message( 'bs-extendedsearch-search-center-filter-type-label' ).plain(),
			filter: {
				label: mw.message( 'bs-extendedsearch-search-center-filter-type-label' ).plain(),
				valueLabel: mw.message( 'bs-extendedsearch-search-center-filter-type-with-values-label' ).plain(),
				id: 'type',
				options: []
			}
		};

		for( idx in availableTypes ) {
			var type = availableTypes[idx];
			typeFilter.filter.options.push( {
				label: type,
				data: type
			} );
		}

		return [ typeFilter ];
	}

	/**
	 * Makes config objects for each of filterable fields
	 * from aggregations returned by the search
	 *
	 * @param {Array} aggs
	 * @returns {Array}
	 */
	function _getFiltersFromAggs( aggs ) {
		var filters = [];
		for( aggKey in aggs ) {
			var agg = aggs[aggKey];
			var filterId = aggKey.slice( 6 );
			var label = mw.message( 'bs-extendedsearch-search-center-filter-' + aggKey + '-label' ).plain();
			var valueLabel = mw.message( 'bs-extendedsearch-search-center-filter-' + aggKey + '-with-values-label' ).plain();
			var filter = {
				label: label,
				filter: {
					label: label,
					valueLabel: valueLabel,
					id: filterId,
					options: []
				}
			};
			for( bucketIdx in agg.buckets ) {
				var bucket = agg.buckets[bucketIdx];
				filter.filter.options.push( {
					label: bucket.key,
					data: bucket.key,
					count: bucket.doc_count
				} );
			}
			filters.push( filter );
		}
		return filters;
	}

	/**
	 * Fills ResultsStrucure object with actual values
	 * returned by search
	 *
	 * @param {Array} results
	 * @returns {Array}
	 */
	function _applyResultsToStructure( results ) {
		var resultStructure = mw.config.get( 'bsgESResultStructure' );
		var structuredResults = [];
		$.each( results, function( idx, result ) {
			var cfg = {};
			//dummy criteria for featured - prototype only
			if( idx < 1 ) {
				cfg.featured = true;
			}
			for( cfgKey in resultStructure ) {
				if( cfgKey == 'secondaryInfos' ) {
					cfg[cfgKey] = {
						top: {
							items: search.formatSecondaryInfoItems(
								resultStructure[cfgKey]['top']['items'],
								result
							)
						},
						bottom: {
							items: search.formatSecondaryInfoItems(
								resultStructure[cfgKey]['bottom']['items'],
								result
							)
						}
					};
					continue;
				}

				var resultKey = resultStructure[ cfgKey ];
				if( ( resultKey in result ) && result[resultKey] != '' ) {
					cfg[cfgKey] = result[resultKey];
				}
			}

			//override values for featured results
			if( cfg.featured == true ) {
				for( featuredField in resultStructure['featured'] ) {
					var resultKey = resultStructure['featured'][featuredField];
					if( !( resultKey in result ) ) {
						continue;
					}
					cfg[featuredField] = result[resultKey];
				}
			}

			structuredResults.push( cfg );
		} );
		return structuredResults;
	}

	/**
	 * Creates config objects for secondary informaions
	 *
	 * @param {Array} items
	 * @param {Array} result
	 * @returns {Array}
	 */
	function _formatSecondaryInfoItems( items, result ) {
		var formattedItems = [];
		for( idx in items ) {
			var item = items[idx];
			if( !( item.name in result ) ) {
				continue;
			}

			if( !result[item.name] ||
				( $.isArray( result[item.name] ) &&  result[item.name].length == 0 ) ) {
				continue;
			}

			formattedItems.push( {
				nolabel: item.nolabel || false,
				labelKey: item.labelKey || 'bs-extendedsearch-search-center-result-' + item.name + '-label',
				name: item.name,
				value: result[item.name]
			} )
		}
		return formattedItems;
	}

	var api = new mw.Api();
	function _execSearch() {
		var resultsPanel = new bs.extendedSearch.ResultsPanel({});

		resultsPanel.clearAll();
		resultsPanel.showLoading();

		var queryData = bs.extendedSearch.utils.getFragment();
		if( $.isEmptyObject( queryData ) ) {
			resultsPanel.removeLoading();
			resultsPanel.showHelp();
			return;
		}

		api.abort();
		api.get( $.extend(
			queryData,
			{
				'action': 'bs-extendedsearch-query'
			}
		) )
		.done( function( response ) {
			//Lookup object might have changed due to LookupModifiers
			search.makeLookup( JSON.parse( response.lookup ) );

			var toolsPanel = new bs.extendedSearch.ToolsPanel( {
				lookup: search.getLookupObject(),
				filterData: $.merge(
					search.getTypeFilter(),
					search.getFiltersFromAggs( response.aggregations )
				)
			} );
			toolsPanel.init();
			if( response.total === 0 ) {
				return resultsPanel.init( {
					results: [],
					total: 0
				} );
			}

			return resultsPanel.init( {
				results: search.applyResultsToStructure( response.results ),
				total: response.total,
				caller: search
			} );
		} );
	}

	function _getPageSizeConfig() {
		return {
			value: this.getLookupObject().getSize(),
			options: [
				{ data: 25 },
				{ data: 50 },
				{ data: 75 },
				{ data: 100 }
			]
		};
	}

	function _getLookupObject() {
		if( !this.lookup ) {
			this.makeLookup({});
		}
		return this.lookup;
	}

	function _makeLookup( config ) {
		config = config || {};
		this.lookup = new bs.extendedSearch.Lookup( config );
		if( this.lookup.getSize() == 0 ) {
			//set default value for page size - prevent zero size pages
			this.lookup.setSize( mw.config.get( 'bsgESResultsPerPage' ) );
		}
		if( this.lookup.getSort().length == 0 ) {
			this.lookup.addSort( 'basename' );
		}
	}

	function _clearLookupObject() {
		this.lookup = null;
	}

	function _resetPagination() {
		this.getLookupObject().setFrom( 0 );
	}

	bs.extendedSearch.SearchCenter = {
		execSearch: _execSearch,
		getLookupObject: _getLookupObject,
		clearLookupObject: _clearLookupObject,
		makeLookup: _makeLookup,
		resetPagination: _resetPagination,
		updateQueryHash: updateQueryHash,
		getPageSizeConfig: _getPageSizeConfig,
		getTypeFilter: _getTypeFilter,
		getFiltersFromAggs: _getFiltersFromAggs,
		applyResultsToStructure: _applyResultsToStructure,
		formatSecondaryInfoItems: _formatSecondaryInfoItems
	};

	var search = bs.extendedSearch.SearchCenter;

	//Init searchBar and wire it up
	var searchBar = new bs.extendedSearch.SearchBar( {
		useNamespacePills: false
	} );

	searchBar.onValueChanged = function() {
		bs.extendedSearch.SearchCenter.resetPagination();
		search.getLookupObject().setSimpleQueryString( this.value );
		updateQueryHash();
	};

	searchBar.onClearSearch = function() {
		bs.extendedSearch.SearchBar.prototype.onClearSearch.call( this );

		search.clearLookupObject();
		search.getLookupObject().setSimpleQueryString( '' );
		updateQueryHash();
	};

	//Init lookup object
	var curQueryData = bs.extendedSearch.utils.getFragment();

	//When coming from search bar there will be at least query string param "q" in the URL.
	//Not removing it because it would cause reload of the page
	//We could also create correct URL in the search bar, but that will not work
	//if user doesnt wait for JS to load
	var queryStringQ = bs.extendedSearch.utils.getQueryStringParam( 'q' );
	var queryStringNs = bs.extendedSearch.utils.getQueryStringParam( 'ns' );

	//if "q" is present in hash, make Lookup from it
	if( "q" in curQueryData ) {
		var config = JSON.parse( curQueryData.q );
		search.makeLookup( config );
		//Update searchBar if page is loaded with query present
		searchBar.setValue( search.getLookupObject().getSimpleQueryString().query );
	} else if( queryStringQ ) {
		//No hash set, but there are query string params
		search.getLookupObject().setSimpleQueryString( queryStringQ );
		if( queryStringNs ) {
			//If namespace pill was set on page user is coming from
			//set default namespace filter - HARDCODED FILTER ID
			search.getLookupObject().addFilter( 'namespace_text', queryStringNs );
		}
		//Update searchBar if page is loaded with query present
		searchBar.setValue( search.getLookupObject().getSimpleQueryString().query );
		updateQueryHash();
	}

	function updateQueryHash() {
		bs.extendedSearch.utils.setFragment( {
			q: JSON.stringify( search.getLookupObject() )
		} );
	}

} )( mediaWiki, jQuery, blueSpice, document );

jQuery(window).on( 'hashchange', function() {
	bs.extendedSearch.SearchCenter.execSearch();
});

jQuery( function() {
	bs.extendedSearch.SearchCenter.execSearch();
});