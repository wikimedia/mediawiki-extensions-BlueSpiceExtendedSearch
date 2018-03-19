( function( mw, $, bs, d, undefined ){

	this.lookup = null;

	var searchField = OO.ui.infuse( 'bs-es-tf-search' );
	var curQueryData = bs.extendedSearch.utils.getFragment();

	//parse 'q' param and make config object from it
	if( "q" in curQueryData ) {
		var config = JSON.parse( curQueryData.q );
		makeLookup( config );
		searchField.setValue( getLookup().getSimpleQueryString().query || '' );
	}

	searchField.on( 'change', function ( value ) {
		if( value.length < 3 ) {
			return;
		}
		getLookup().setFrom( 0 );
		getLookup().setSimpleQueryString( value );
		updateQueryHash();
	} );

	function getLookup() {
		if( this.lookup === null ) {
			makeLookup({});
		}
		return this.lookup;
	}

	function makeLookup( config ) {
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

	function updateQueryHash() {
		bs.extendedSearch.utils.setFragment( {
			q: JSON.stringify( getLookup() )
		} );
	}

	/**
	 * Makes config object for special Type filter
	 * This filter contains different results types (one for each source)
	 * that can be filtered
	 *
	 * @returns {Array}
	 */
	function getTypeFilter() {
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
	function getFiltersFromAggs( aggs ) {
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
	function applyResultsToStructure( results ) {
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
							items: formatSecondaryInfoItems(
								resultStructure[cfgKey]['top']['items'],
								result
							)
						},
						bottom: {
							items: formatSecondaryInfoItems(
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
	function formatSecondaryInfoItems( items, result ) {
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
		bs.extendedSearch.ResultsPanel.clearAll();
		searchField.popPending();

		var queryData = bs.extendedSearch.utils.getFragment();
		if( $.isEmptyObject( queryData ) ) {
			return;
		}

		searchField.pushPending();

		api.abort();
		api.get( $.extend(
			queryData,
			{
				'action': 'bs-extendedsearch-query'
			}
		) )
		.done( function( response ) {
			bs.extendedSearch.ToolsPanel.setFilterData(
				$.merge(
					getTypeFilter(),
					getFiltersFromAggs( response.aggregations )
				)
			);
			bs.extendedSearch.ToolsPanel.init();

			if( response.total === 0 ) {
				bs.extendedSearch.ResultsPanel.showNoResults();
				searchField.popPending();
				return;
			}

			bs.extendedSearch.ResultsPanel.showResults(
				applyResultsToStructure( response.results ),
				response.total
			);
			searchField.popPending();
		} );
	}

	function _getPageSizeConfig() {
		return {
			value: getLookup().getSize(),
			options: [
				{ data: 25 },
				{ data: 50 },
				{ data: 75 },
				{ data: 100 }
			]
		};
	}

	function _getLookupObject() {
		return getLookup();
	}

	function _resetPagination() {
		getLookup().setFrom( 0 );
	}

	bs.extendedSearch.SearchCenter = {
		execSearch: _execSearch,
		getLookupObject: _getLookupObject,
		resetPagination: _resetPagination,
		updateQueryHash: updateQueryHash,
		getPageSizeConfig: _getPageSizeConfig
	};
} )( mediaWiki, jQuery, blueSpice, document );

jQuery(window).on( 'hashchange', function() {
	bs.extendedSearch.SearchCenter.execSearch();
});

jQuery( function() {
	bs.extendedSearch.SearchCenter.execSearch();
});