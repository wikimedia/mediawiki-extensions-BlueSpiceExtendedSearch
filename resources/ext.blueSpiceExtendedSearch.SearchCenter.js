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
				hasHiddenLabelKey: 'bs-extendedsearch-search-center-filter-has-hidden',
				id: 'type',
				options: [],
				group: 'root'
			}
		};

		for( var idx in availableTypes ) {
			var type = availableTypes[idx];
			var message = type;
			if( mw.message( 'bs-extendedsearch-source-type-' + type + '-label' ).exists()  ) {
				message = mw.message( 'bs-extendedsearch-source-type-' + type + '-label' ).plain();
			}

			typeFilter.filter.options.push( {
				label: message || type,
				data: type
			} );
		}

		return [ typeFilter ];
	}

	/**
	 * Makes config objects for each of filterable fields
	 * from aggregations returned by the search
	 *
	 * @param {Object} rawFilters
	 * @returns {Array}
	 */
	function _getFilters( rawFilters ) {
		var filters = [];
		for( var filterId in rawFilters ) {
			var rawFilter = rawFilters[filterId];
			//TODO: Change this with some mechanism to get label keys
			var labelFilterId = filterId.replace( '.', '-' );
			var label = rawFilter.label || mw.message( 'bs-extendedsearch-search-center-filter-' + labelFilterId + '-label' ).plain();
			var valueLabel = rawFilter.valueLabel || mw.message( 'bs-extendedsearch-search-center-filter-' + labelFilterId + '-with-values-label' ).plain();
			var filter = {
				label: label,
				group: rawFilter.group || 'root',
				filter: {
					label: label,
					valueLabel: valueLabel,
					hasHiddenLabelKey: 'bs-extendedsearch-search-center-filter-has-hidden',
					id: filterId,
					isANDEnabled: rawFilter.isANDEnabled,
					multiSelect: rawFilter.multiSelect,
					options: []
				}
			};

			for( var bucketIdx in rawFilter.buckets ) {
				var bucket = rawFilter.buckets[bucketIdx];
				filter.filter.options.push( {
					label: bucket.label || bucket.key,
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
		var resultStructures = mw.config.get( 'bsgESResultStructures' );
		var structuredResults = [];

		$.each( results, function( idx, result ) {
			if( result.is_redirect ) {
				structuredResults.push( {
					is_redirect: true,
					page_anchor: result.page_anchor,
					redirect_target_anchor: result.redirect_target_anchor,
					image_uri: result.image_uri,
					_id: result.id,
					raw_result: result
				} );
				return;
			}
			var resultStructure = resultStructures[result["type"]];
			var cfg = {};
			//dummy criteria for featured - prototype only
			if( result.featured == 1 ) {
				cfg.featured = true;
			}

			for( var cfgKey in resultStructure ) {
				if( cfgKey === 'secondaryInfos' ) {
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
				var keyValue = search.getResultValueByKey( result, resultKey );
				if( keyValue !== false ) {
					cfg[cfgKey] = keyValue;
				}
			}

			//override values for featured results
			if( cfg.featured === true ) {
				for( var featuredField in resultStructure['featured'] ) {
					var resultKey = resultStructure['featured'][featuredField];
					var keyValue = search.getResultValueByKey( result, resultKey );
					if( !( keyValue ) ) {
						continue;
					}
					cfg[featuredField] = keyValue;
				}
			}

			cfg._id = result.id;
			cfg.raw_result = result;
			cfg.user_relevance = result.user_relevance;

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
		for( var idx in items ) {
			var item = items[idx];
			if( !( item.name in result ) ) {
				continue;
			}

			var keyValue = search.getResultValueByKey( result, item.name );
			if( !keyValue || ( $.isArray( keyValue ) &&  keyValue.length === 0 ) ) {
				continue;
			}

			formattedItems.push( {
				nolabel: item.nolabel || false,
				labelKey: item.labelKey || 'bs-extendedsearch-search-center-result-' + item.name + '-label',
				name: item.name,
				value: keyValue
			} );
		}
		return formattedItems;
	}

	/**
	 * Gets the value for the given key from result
	 * Key can be a path ( level.sublevel.name )
	 *
	 * @param {Array} result
	 * @param {string} key
	 * @returns {string}|false if not present
	 */
	function _getResultValueByKey( result, key ) {
		var value = false;
		if( typeof( key ) !== 'string' ) {
			return value;
		}

		var keyBits = key.split( '.' );
		for( var bitIdx in keyBits ) {
			var keyBit = keyBits[bitIdx];
			if( result[keyBit] ) {
				result = result[keyBit];
				value = result;
			}
		}
		if( value === '' ) {
			value = false;
		}

		return value;
	}

	var api = new mw.Api();
	function _execSearch() {
		var $resultCnt = $( '#bs-es-results' );
		var $toolsCnt = $( '#bs-es-tools' );
		var $altSearchCnt = $( '#bs-es-alt-search' );

		$resultCnt.children().remove();
		$toolsCnt.children().remove();
		$toolsCnt.removeClass( 'bs-es-tools' );
		$altSearchCnt.children().remove();
		search.showLoading();

		var queryData = bs.extendedSearch.utils.getFragment();
		if( $.isEmptyObject( queryData ) || searchBar.$searchBox.val() === '' ) {
			search.removeLoading();
			$resultCnt.append( new bs.extendedSearch.ResultMessage( {
				mode: 'help'
			} ).$element );
			$resultCnt.trigger( 'resultsReady' );
			return;
		}
		queryData.searchTerm = searchBar.$searchBox.val();

		var searchPromise = this.runApiCall( queryData );
		$( d ).trigger( 'BSExtendedSearchSearchCenterExecSearch', [ queryData, search ] );

		searchPromise.done( function( response ) {
			if( response.exception ) {
				search.removeLoading();
				$resultCnt.trigger( 'resultsReady' );
				return $resultCnt.append( new bs.extendedSearch.ResultMessage( {
					mode: 'error'
				} ).$element );
			}
			//Lookup object might have changed due to LookupModifiers
			search.makeLookup( JSON.parse( response.lookup ) );

			var term = this.getLookupObject().getQueryString().query || '';
			var hitCount =  new bs.extendedSearch.HitCountWidget( {
				term: term,
				count: response.total,
				total_approximated: response.total_approximated
			} );

			var spellCheck = new bs.extendedSearch.SpellcheckWidget( response.spellcheck );
			spellCheck.$element.on( 'forceSearchTerm', this.forceSearchTerm.bind( this ) );
			if( bs.extendedSearch.utils.isMobile() ) {
				$altSearchCnt.addClass( 'mobile' );
			}
			$altSearchCnt.append( spellCheck.$element );

			var toolsPanel = new bs.extendedSearch.ToolsPanel( {
				lookup: search.getLookupObject(),
				filterData: $.merge(
					search.getTypeFilter(),
					search.getFilters( response.filters )
				),
				caller: search,
				mobile: bs.extendedSearch.utils.isMobile(),
				defaultFilters: mw.config.get( 'ESSearchCenterDefaultFilters' ),
				hitCounter: hitCount,
				pageCreateData: response.page_create_data || {}
			} );

			toolsPanel.init();

			if( response.total === 0 ) {
				search.removeLoading();
				return $resultCnt.append( new bs.extendedSearch.ResultMessage( {
					mode: 'noResults'
				} ).$element );
			}

			var resultPanel = new bs.extendedSearch.ResultsPanel( {
				$element: $resultCnt,
				results: search.applyResultsToStructure( response.results ),
				total: response.total,
				spellcheck: response.spellcheck,
				caller: search,
				total_approximated: response.total_approximated,
				mobile: bs.extendedSearch.utils.isMobile()
			} );
			$resultCnt.append( resultPanel.$element );

			$resultCnt.trigger( 'resultsReady' );
			search.removeLoading();
		}.bind( this ) );
	}

	function _showLoading() {
		if( $( '.bs-extendedsearch-searchcenter-loading' ).length > 0 ) {
			return;
		}

		var pbWidget = new OO.ui.ProgressBarWidget({
			progress: false
		} );

		//Insert loader before results div to avoid reseting it
		$( '#bs-es-results' ).before(
			$( '<div>' )
				.addClass( 'bs-extendedsearch-searchcenter-loading' )
				.append( pbWidget.$element )
		);
		$( '#bs-es-tools, #bs-es-results' ).hide();
	}

	function _removeLoading() {
		$( '.bs-extendedsearch-searchcenter-loading' ).remove();
		$( '#bs-es-tools, #bs-es-results' ).show();
	}

	function _runApiCall( queryData, action ) {
		action = action || 'bs-extendedsearch-query';

		api.abort();
		return api.get( $.extend(
			queryData,
			{
				'action': action
			}
		) );
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
		if( this.lookup.getSize() === 0 ) {
			//set default value for page size - prevent zero size pages
			this.lookup.setSize( mw.config.get( 'bsgESResultsPerPage' ) );
		}
		//Default sorter
		if( this.lookup.getSort().length === 0 ) {
			this.lookup.addSort( '_score', bs.extendedSearch.Lookup.SORT_DESC );
		}
	}

	function _clearLookupObject() {
		this.lookup = null;
	}

	/**
	 * Handles term forcing from spellcheck -
	 * if user decides to override auto spellcheck
	 */
	function _forceSearchTerm( e, params ) {
		//Start fresh search
		this.clearLookupObject();
		this.getLookupObject().setQueryString( params.term );
		if( params.force ) {
			this.getLookupObject().setForceTerm();
		}
		searchBar.setValue( params.term );

		updateQueryHash();
	}

	bs.extendedSearch.SearchCenter = {
		execSearch: _execSearch,
		getLookupObject: _getLookupObject,
		clearLookupObject: _clearLookupObject,
		makeLookup: _makeLookup,
		updateQueryHash: updateQueryHash,
		getPageSizeConfig: _getPageSizeConfig,
		getTypeFilter: _getTypeFilter,
		getFilters: _getFilters,
		applyResultsToStructure: _applyResultsToStructure,
		formatSecondaryInfoItems: _formatSecondaryInfoItems,
		getResultValueByKey: _getResultValueByKey,
		forceSearchTerm: _forceSearchTerm,
		runApiCall: _runApiCall,
		showLoading: _showLoading,
		removeLoading: _removeLoading
	};

	var search = bs.extendedSearch.SearchCenter;

	//Init searchBar and wire it up
	var searchBar = new bs.extendedSearch.SearchBar( {
		useNamespacePills: false,
		useSubpagePills: false
	} );

	searchBar.$searchForm.on( 'submit', function( e ) {
		e.preventDefault();
		bs.extendedSearch.SearchCenter.execSearch();
	} );


	searchBar.onValueChanged = function() {
		search.getLookupObject().removeForceTerm();
		search.getLookupObject().setQueryString( this.value );
		updateQueryHash();
	};

	searchBar.onClearSearch = function() {
		bs.extendedSearch.SearchBar.prototype.onClearSearch.call( this );

		search.clearLookupObject();
		bs.extendedSearch.utils.clearFragment();
	};

	function updateQueryHash() {
		bs.extendedSearch.utils.setFragment({
			q: JSON.stringify(search.getLookupObject())
		});
	}

	//Init lookup object - get lookup config any way possible
	var fragmentParams = bs.extendedSearch.utils.getFragment();
	var updateHash = true;
	var config;

	if( "q" in fragmentParams ) {
		//Try getting lookup from fragment - it has top prio
		config = JSON.parse( fragmentParams.q );
		updateHash = false;
	} else {
		//Get lookup configuration from pre-set variable
		config = JSON.parse( mw.config.get( 'bsgLookupConfig' ) );
	}

	if( $.isEmptyObject( config ) == false ) {
		search.makeLookup( config );
		//Update searchBar if page is loaded with query present
		var query = search.getLookupObject().getQueryString();
		if( query ) {
			searchBar.setValue( query.query );
		}
		if( updateHash ) {
			updateQueryHash();
		}

		//Remove query string param "q" in case its set
		var queryStringParam = bs.extendedSearch.utils.getQueryStringParam( 'q' );
		if( queryStringParam ) {
			bs.extendedSearch.utils.removeQueryStringParam( 'q' );
		}
	}

} )( mediaWiki, jQuery, blueSpice, document );

jQuery(window).on( 'hashchange', function() {
	bs.extendedSearch.SearchCenter.execSearch();
});

jQuery( function() {
	bs.extendedSearch.SearchCenter.execSearch();
});
