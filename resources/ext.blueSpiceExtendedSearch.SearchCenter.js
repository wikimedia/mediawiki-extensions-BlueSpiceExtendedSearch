/* eslint-disable camelcase */
( function ( mw, $, bs, d ) {
	let searchBar;

	/**
	 * Makes config object for special Type filter
	 * This filter contains different results types (one for each source)
	 * that can be filtered
	 *
	 * @return {Array}
	 */
	function _getTypeFilter() {
		if ( !mw.config.get( 'bsgESEnableTypeFilter' ) ) {
			return [];
		}
		const availableTypes = mw.config.get( 'bsgESAvailableTypes' );

		if ( availableTypes.length === 0 ) {
			return [];
		}

		const typeFilter = {
			label: mw.message( 'bs-extendedsearch-search-center-filter-type-label' ).text(),
			filter: {
				label: mw.message( 'bs-extendedsearch-search-center-filter-type-label' ).text(),
				valueLabel: mw.message( 'bs-extendedsearch-search-center-filter-type-with-values-label' ).text(),
				hasHiddenLabelKey: 'bs-extendedsearch-search-center-filter-has-hidden',
				id: 'type',
				options: [],
				group: 'root'
			}
		};

		for ( let idx = 0; idx < availableTypes.length; idx++ ) {
			const type = availableTypes[ idx ];
			let message = type;
			if ( mw.message( 'bs-extendedsearch-source-type-' + type + '-label' ).exists() ) { // eslint-disable-line mediawiki/msg-doc
				message = mw.message( 'bs-extendedsearch-source-type-' + type + '-label' ).text(); // eslint-disable-line mediawiki/msg-doc
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
	 * @return {Array}
	 */
	function _getFilters( rawFilters ) {
		const filters = [];
		for ( const filterId in rawFilters ) {
			if ( !rawFilters.hasOwnProperty( filterId ) ) {
				continue;
			}
			const rawFilter = rawFilters[ filterId ];
			// TODO: Change this with some mechanism to get label keys
			const labelFilterId = filterId.replace( '.', '-' );
			const label = rawFilter.label || mw.message( 'bs-extendedsearch-search-center-filter-' + labelFilterId + '-label' ).text(); // eslint-disable-line mediawiki/msg-doc
			const valueLabel = rawFilter.valueLabel || mw.message( 'bs-extendedsearch-search-center-filter-' + labelFilterId + '-with-values-label' ).text(); // eslint-disable-line mediawiki/msg-doc
			const filter = {
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

			if ( !Array.isArray( rawFilter.buckets ) ) {
				continue;
			}
			for ( let i = 0; i < rawFilter.buckets.length; i++ ) {
				const bucket = rawFilter.buckets[ i ];
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
	 * @return {Array}
	 */
	function _applyResultsToStructure( results ) {
		const resultStructures = mw.config.get( 'bsgESResultStructures' );
		const structuredResults = [];

		$.each( results, ( idx, result ) => { // eslint-disable-line no-jquery/no-each-util
			if ( result.is_redirect ) {
				structuredResults.push( {
					is_redirect: true,
					page_anchor: result.page_anchor,
					redirect_target_anchor: result.redirect_target_anchor,
					namespace_text: result.namespace_text,
					breadcrumbs: result.breadcrumbs,
					_id: result.id,
					raw_result: result
				} );
				return;
			}
			const resultStructure = resultStructures[ result.type ];
			const cfg = {};
			// dummy criteria for featured - prototype only
			if ( result.featured === 1 ) {
				cfg.featured = true;
			}

			for ( const cfgKey in resultStructure ) {
				if ( !resultStructure.hasOwnProperty( cfgKey ) ) {
					continue;
				}
				if ( cfgKey === 'secondaryInfos' ) {
					cfg[ cfgKey ] = {
						top: {
							items: bs.extendedSearch.SearchCenter.formatSecondaryInfoItems(
								resultStructure[ cfgKey ].top.items,
								result
							)
						},
						bottom: {
							items: bs.extendedSearch.SearchCenter.formatSecondaryInfoItems(
								resultStructure[ cfgKey ].bottom.items,
								result
							)
						}
					};
					continue;
				}

				const resultKey = resultStructure[ cfgKey ];
				const keyValue = bs.extendedSearch.SearchCenter.getResultValueByKey( result, resultKey );
				if ( keyValue !== false ) {
					cfg[ cfgKey ] = keyValue;
				}
			}

			// override values for featured results
			if ( cfg.featured === true ) {
				for ( const featuredField in resultStructure.featured ) {
					if ( !resultStructure.featured.hasOwnProperty( featuredField ) ) {
						continue;
					}
					const resultKey = resultStructure.featured[ featuredField ];
					const keyValue = bs.extendedSearch.SearchCenter.getResultValueByKey( result, resultKey );
					if ( !( keyValue ) ) {
						continue;
					}
					cfg[ featuredField ] = keyValue;
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
	 * Creates config objects for secondary informations
	 *
	 * @param {Array} items
	 * @param {Array} result
	 * @return {Array}
	 */
	function _formatSecondaryInfoItems( items, result ) {
		const formattedItems = [];

		for ( let i = 0; i < items.length; i++ ) {
			const item = items[ i ];
			if ( !( item.name in result ) ) {
				continue;
			}

			const keyValue = bs.extendedSearch.SearchCenter.getResultValueByKey( result, item.name );
			if ( !keyValue || ( Array.isArray( keyValue ) && keyValue.length === 0 ) ) {
				continue;
			}

			formattedItems.push( {
				nolabel: item.nolabel || false,
				labelKey: item.labelKey || 'bs-extendedsearch-search-center-result-' + item.name + '-label',
				name: item.name,
				value: keyValue,
				showInRightLinks: item.showInRightLinks || false
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
	 * @return {string}|false if not present
	 */
	function _getResultValueByKey( result, key ) {
		let value = false;
		if ( typeof ( key ) !== 'string' ) {
			return value;
		}

		const keyBits = key.split( '.' );
		for ( let i = 0; i < keyBits.length; i++ ) {
			const keyBit = keyBits[ i ];
			if ( result[ keyBit ] ) {
				result = result[ keyBit ];
				value = result;
			}
		}
		if ( value === '' ) {
			value = false;
		}

		return value;
	}

	const api = new mw.Api();
	function _execSearch() {
		const $searchCnt = $( '#bs-es-searchcenter' );
		const $resultCnt = $( '#bs-es-results' );
		const $toolsCnt = $( '#bs-es-tools' );
		const $altSearchCnt = $( '#bs-es-alt-search' );

		$resultCnt.children().remove();
		$toolsCnt.children().remove();
		$toolsCnt.removeClass( 'bs-es-tools' );
		$altSearchCnt.children().remove();
		bs.extendedSearch.SearchCenter.showLoading();

		const queryData = bs.extendedSearch.utils.getFragment();
		if ( $.isEmptyObject( queryData ) || searchBar.$searchBox.val() === '' ) {
			mw.hook( 'bs.extendedsearch.searchcenter.getResults' ).fire( $searchCnt, { total: 0, results: [] }, {} );
			bs.extendedSearch.SearchCenter.removeLoading();
			$resultCnt.append( new bs.extendedSearch.ResultMessage( {
				mode: 'help'
			} ).$element );
			$resultCnt.trigger( 'resultsReady' );
			return;
		}
		queryData.searchTerm = searchBar.$searchBox.val();
		mw.hook( 'bs.extendedsearch.searchcenter.execSearch' ).fire( $searchCnt, queryData );
		const searchPromise = this.runApiCall( queryData );

		$( d ).trigger( 'BSExtendedSearchSearchCenterExecSearch', [ queryData, bs.extendedSearch.SearchCenter ] );

		searchPromise.done( ( response ) => {
			mw.hook( 'bs.extendedsearch.searchcenter.getResults' ).fire( $searchCnt, response, queryData );
			if ( response.exception ) {
				bs.extendedSearch.SearchCenter.removeLoading();
				$resultCnt.trigger( 'resultsReady' );
				return $resultCnt.append( new bs.extendedSearch.ResultMessage( {
					mode: 'error'
				} ).$element );
			}
			// Lookup object might have changed due to LookupModifiers
			bs.extendedSearch.SearchCenter.makeLookup( JSON.parse( response.lookup ) );

			const term = this.getLookupObject().getQueryString().query || '';
			const hitCount = new bs.extendedSearch.HitCountWidget( {
				term: term,
				count: response.total,
				total_approximated: response.total_approximated,
				spellCheck: response.spellcheck || false
			} );

			const spellCheck = new bs.extendedSearch.SpellcheckWidget( response.spellcheck );
			spellCheck.$element.on( 'forceSearchTerm', this.forceSearchTerm.bind( this ) );
			if ( bs.extendedSearch.utils.isMobile() ) {
				$altSearchCnt.addClass( 'mobile' );
			}
			$altSearchCnt.append( spellCheck.$element );

			const suggestOperator = new bs.extendedSearch.OperatorSuggest( {
				lookup: bs.extendedSearch.SearchCenter.getLookupObject(),
				searchBar: searchBar
			} );
			$altSearchCnt.append( suggestOperator.$element );

			const toolsPanel = new bs.extendedSearch.ToolsPanel( {
				lookup: bs.extendedSearch.SearchCenter.getLookupObject(),
				filterData: $.merge(
					bs.extendedSearch.SearchCenter.getTypeFilter(),
					bs.extendedSearch.SearchCenter.getFilters( response.filters )
				),
				caller: bs.extendedSearch.SearchCenter,
				mobile: bs.extendedSearch.utils.isMobile(),
				defaultFilters: mw.config.get( 'ESSearchCenterDefaultFilters' ),
				hitCounter: hitCount
			} );
			$toolsCnt.append( toolsPanel.$element );
			toolsPanel.init();

			if ( response.total === 0 ) {
				bs.extendedSearch.SearchCenter.removeLoading();
				$resultCnt.trigger( 'resultsReady' );
				return $resultCnt.append( new bs.extendedSearch.ResultMessage( {
					mode: 'noResults'
				} ).$element );
			}

			const resultPanel = new bs.extendedSearch.ResultsPanel( {
				$element: $resultCnt,
				results: bs.extendedSearch.SearchCenter.applyResultsToStructure( response.results ),
				total: response.total,
				spellcheck: response.spellcheck,
				caller: bs.extendedSearch.SearchCenter,
				total_approximated: response.total_approximated,
				mobile: bs.extendedSearch.utils.isMobile(),
				searchAfter: response.search_after || []
			} );
			resultPanel.on( 'resultsAdded', ( resultsAdded ) => {
				$resultCnt.trigger( 'resultsUpdated', [ resultPanel, resultsAdded ] );
			} );
			$resultCnt.append( resultPanel.$element );

			bs.extendedSearch._registerTrackableLinks();
			$resultCnt.trigger( 'resultsReady', [ resultPanel ] );
			bs.extendedSearch.SearchCenter.removeLoading();
			$( resultPanel.$element.children()[ 0 ] ).find( 'a' )[ 0 ].focus();
			// Done afterwards to announce properly
			hitCount.init();
		} );
	}

	function _showLoading() {
		if ( $( '.bs-extendedsearch-searchcenter-loading' ).length > 0 ) {
			return;
		}

		const pbWidget = new OO.ui.ProgressBarWidget( {
			progress: false
		} );

		// Insert loader before results div to avoid reseting it
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
		return api.get( Object.assign(
			queryData,
			{
				action: action
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
		if ( !this.lookup ) {
			this.makeLookup( {} );
		}
		return this.lookup;
	}

	function _makeLookup( config ) {
		config = config || {};
		this.lookup = new bs.extendedSearch.Lookup( config );

		this.optionStorage = new bs.extendedSearch.OptionStorage();
		const options = this.optionStorage.getOptions();
		if ( options.pageSize ) {
			this.lookup.setSize( parseInt( options.pageSize ) );
		} else if ( this.lookup.getSize() === 0 ) {
			// set default value for page size - prevent zero size pages
			this.lookup.setSize( mw.config.get( 'bsgESResultsPerPage' ) );
		}
		if ( options.sortBy ) {
			this.lookup.sort = [];
			for ( let i = 0; i < options.sortBy.length; i++ ) {
				this.lookup.addSort( options.sortBy[ i ], options.sortOrder );
			}
		} else if ( this.lookup.getSort().length === 0 ) {
			this.lookup.addSort( '_score', bs.extendedSearch.Lookup.SORT_DESC );
		}

		mw.hook( 'bs.extendedSearch.makeLookup' ).fire( this.lookup );
	}

	function _clearLookupObject() {
		this.lookup = null;
	}

	/**
	 * Handles term forcing from spellcheck -
	 * if user decides to override auto spellcheck
	 *
	 * @param {Event} e
	 * @param {Object} params
	 */
	function _forceSearchTerm( e, params ) {
		// Start fresh search
		this.clearLookupObject();
		this.getLookupObject().setQueryString( params.term );
		if ( params.force ) {
			this.getLookupObject().setForceTerm();
		}
		searchBar.setValue( params.term );

		updateQueryHash();
	}

	function updateQueryHash( lookup ) {
		lookup = lookup || bs.extendedSearch.SearchCenter.getLookupObject();
		bs.extendedSearch.utils.setFragment( {
			q: JSON.stringify( lookup )
		} );
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

	$( () => {
		// Init searchBar and wire it up
		searchBar = new bs.extendedSearch.SearchBar( {
			useNamespacePills: false,
			useSubpagePills: false,
			typingDoneInterval: 1000,
			isSearchCenter: true
		} );

		searchBar.$searchForm.on( 'submit', ( e ) => {
			e.preventDefault();
			bs.extendedSearch.SearchCenter.execSearch();
		} );

		searchBar.on( 'valueChanged', () => {
			bs.extendedSearch.SearchCenter.getLookupObject().removeForceTerm();
			bs.extendedSearch.SearchCenter.getLookupObject().setQueryString( searchBar.value );
			bs.extendedSearch.SearchCenter.updateQueryHash();
		} );

		searchBar.on( 'clearSearch', () => {
			bs.extendedSearch.SearchCenter.clearLookupObject();
			bs.extendedSearch.utils.clearFragment();
		} );

		// Init lookup object - get lookup config any way possible
		const fragmentParams = bs.extendedSearch.utils.getFragment();
		let updateHash = true;
		let config;

		if ( 'q' in fragmentParams ) {
			// Try getting lookup from fragment - it has top prio
			config = JSON.parse( fragmentParams.q );
			updateHash = false;
		} else {
			// Get lookup configuration from pre-set variable
			config = JSON.parse( mw.config.get( 'bsgLookupConfig' ) );
		}

		if ( $.isEmptyObject( config ) === false ) {
			bs.extendedSearch.SearchCenter.makeLookup( config );
			// Update searchBar if page is loaded with query present
			const query = bs.extendedSearch.SearchCenter.getLookupObject().getQueryString();
			if ( query ) {
				searchBar.setValue( query.query );
			}
			if ( updateHash ) {
				bs.extendedSearch.SearchCenter.updateQueryHash();
			}

			// Remove query string params passed once we set the hash
			bs.extendedSearch.utils.removeQueryStringParams( [ 'q', 'raw_term', 'fulltext' ] );
		}

		bs.extendedSearch.SearchCenter.execSearch();

		$( window ).on( 'hashchange', () => {
			bs.extendedSearch.SearchCenter.execSearch();
		} );

		$( d ).trigger( 'BSExtendedSearchInit', [ bs.extendedSearch.SearchCenter, searchBar ] );
	} );

}( mediaWiki, jQuery, blueSpice, document ) );
