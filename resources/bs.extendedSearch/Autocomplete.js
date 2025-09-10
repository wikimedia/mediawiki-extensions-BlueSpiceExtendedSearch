/* eslint-disable camelcase */
bs.util.registerNamespace( 'bs.extendedSearch' );

bs.extendedSearch.Autocomplete = function ( searchBar, cfg ) {
	cfg = cfg || {};
	this.searchBar = searchBar;
	this.lookupConfig = cfg.lookupConfig || {};
	this.api = new mw.Api();
	this.suggestField = 'suggestions';
	// Show secondary results if there are less then this many primary results
	this.secondaryThreshold = 0;
	this.displayLimits = {
		primary: 10,
		secondary: 3
	};
	this.enableSearchContexts = typeof cfg.enableSearchContexts === 'undefined' ? true : cfg.enableSearchContexts;

	this.mainpage = '';

	// Wire the events
	this.searchBar.$searchForm.on( 'submit', this.onSubmit.bind( this ) );
	this.searchBar.$searchForm.on( 'focusout', this.onFocusOut.bind( this ) );
	this.searchBar.connect( this, {
		beforeValueChanged: 'beforeValueChanged',
		valueChanged: 'onValueChanged',
		clearSearch: 'removePopup',
		emptyFocus: 'onEmptyFocus'
	} );

	$( window ).on( 'click', this.onWindowClick.bind( this ) );
};

OO.initClass( bs.extendedSearch.Autocomplete );

bs.extendedSearch.Autocomplete.prototype.performSearch = async function ( value ) {
	this.removePopup();
	const suggestions = await this.getSuggestions( value );
	const primary = this.filterByRank( suggestions, 'primary' );
	this.makePopup( primary );
	if ( this.popup.getDisplayedResults().primary.length > this.secondaryThreshold ) {
		return;
	}

	const secondary = this.filterByRank( suggestions, 'secondary' );
	if ( secondary.length > 0 ) {
		this.popup.addSecondary( secondary );
		// There are secondary suggestions already in result set
		return;
	}

	try {
		const fuzzyResults = await this.getSecondaryResults( suggestions );
		this.popup.addSecondary( fuzzyResults );
		bs.extendedSearch._registerTrackableLinks();
	} catch ( e ) {
		// Ignore
	}
};

bs.extendedSearch.Autocomplete.prototype.getSuggestions = async function () {
	const dfd = $.Deferred();
	this.searchBar.setPending();
	const lookup = new bs.extendedSearch.Lookup( this.lookupConfig );

	// We set the AC query term to lowercase always, as that will work with tokenizer
	lookup.setMultiMatchQuery( this.suggestField, this.searchBar.value.toLowerCase() );
	// We are setting excessive size here due to how autocomplete ranking works,
	// It will not focus on returning "best" matches necessarily, but rather
	// the first ones that match the query. This is why we need to get a lot of results,
	// to be able to do our own ranking on a larger set of results.
	// Difference in performance: 10 results = 0.1s, 100 results = 0.3s
	lookup.setSize( 50 );
	if ( $.isEmptyObject( this.searchBar.namespace ) === false ) {
		lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
	}

	if ( this.searchBar.mainpage ) {
		const mainpage = this.searchBar.mainpage;
		const mainpageQuery = { regexp: {} };
		mainpageQuery.regexp.basename_exact = mainpage + '|' + mainpage + '/.*';
		const origMatch = lookup.query.bool.must;
		lookup.query.bool.must = [
			origMatch,
			mainpageQuery
		];

		// We dont want to search for subpages of a page with this name
		// in all namespaces. If ns is not set search in NS_MAIN
		if ( $.isEmptyObject( this.searchBar.namespace ) ) {
			lookup.addTermFilter( 'namespace', 0 );
		}
	}

	this.runLookup( lookup ).done( ( response ) => {
		this.searchBar.clearPending();
		$( document ).trigger( 'BSExtendedSearchAutocompleteSuggestionsRetrieved', [ response.suggestions || [] ] );
		dfd.resolve( response.suggestions );
	} ).fail( () => {
		this.searchBar.clearPending();
	} );
	return dfd.promise();
};

bs.extendedSearch.Autocomplete.prototype.getSecondaryResults = async function ( primarySuggestions ) {
	const dfd = $.Deferred();
	const lookup = new bs.extendedSearch.Lookup( this.lookupConfig );

	lookup.setBoolMatchQueryString( this.suggestField, this.searchBar.value );
	if ( this.searchBar.namespace.id ) {
		if ( primarySuggestions.length === 0 ) {
			// If we are in NS and there are no primary results, look for fuzzy in this NS
			lookup.setBoolMatchQueryFuzziness( this.suggestField, 2, { prefix_length: 1 } );
			lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
		} else {
			// Search for non-fuzzy matches in other namespaces
			lookup.addBoolMustNotTerms( 'namespace', this.searchBar.namespace.id );
			lookup.setSize( 5 );
		}
	} else {
		lookup.setBoolMatchQueryFuzziness( this.suggestField, 2, { prefix_length: 1 } );
		// Do not find non-fuzzy matches
		lookup.addBoolMustNotTerms( this.suggestField, this.searchBar.value );
		lookup.setSize( 5 );
	}

	this.runLookup( lookup, {
		secondaryRequestData: JSON.stringify( {
			primary_suggestions: primarySuggestions.map( ( suggestion ) => suggestion.id || '' )
		} )
	} ).done( ( response ) => {
		dfd.resolve( response.suggestions || [] );
	} ).fail( () => {
		dfd.reject();
	} );
	return dfd.promise();
};

bs.extendedSearch.Autocomplete.prototype.runLookup = function ( lookup, data ) {
	data = data || {};

	const queryData = Object.assign( {
		q: JSON.stringify( lookup ),
		searchData: JSON.stringify( {
			namespace: this.searchBar.namespace.id || 0,
			value: this.searchBar.value,
			mainpage: this.searchBar.mainpage || ''
		} )
	}, data );
	this.api.abort();

	return this.api.get( Object.assign( queryData, { action: 'bs-extendedsearch-autocomplete' } ) );
};

bs.extendedSearch.Autocomplete.prototype.filterByRank = function ( suggestions, rank ) {
	const res = [];
	for ( let i = 0; i < suggestions.length; i++ ) {
		const suggestion = suggestions[ i ];
		if ( suggestion.rank === rank ) {
			res.push( suggestion );
		}
	}
	return res;
};

bs.extendedSearch.Autocomplete.prototype.makePopup = function ( suggestions, headerText ) {
	if ( this.popup ) {
		this.removePopup();
	}

	const popupCfg = {
		searchForm: this.searchBar.$searchForm,
		data: suggestions,
		searchTerm: this.searchBar.value,
		namespaceId: this.searchBar.namespace.id || 0,
		quietSubpage: this.searchBar.getMasterFilterPage(),
		autocomplete: this,
		headerText: headerText,
		displayLimits: this.displayLimits
	};

	this.popup = new bs.extendedSearch.AutocompletePopup( popupCfg );
	this.popup.connect( this, {
		quietSubpageRemoved: function () {
			this.searchBar.suppressQuietSubpage( 'suppress' );
			this.searchBar.changeValue( this.searchBar.$searchBox.val() );
		},
		closePopup: function () {
			this.removePopup();
		}
	} );

	this.popup.$element.css( 'top', ( this.searchBar.$searchBox.outerHeight() + 12 ) + 'px' );
	this.popup.$element.addClass( 'searchbar-autocomplete-results' );
	this.popup.$element.insertAfter( $( '#' + this.searchBar.$searchBoxWrapper.attr( 'id' ) ) );
	this.popup.$element.attr( 'id', this.searchBar.$searchBoxWrapper.attr( 'id' ) + '-results' );

	bs.extendedSearch._registerTrackableLinks();
	this.searchBar.suppressQuietSubpage( 'arm' );
};

bs.extendedSearch.Autocomplete.prototype.beforeValueChanged = function ( e, shouldAbort ) {
	if ( e.type !== 'keyup' ) {
		shouldAbort.abort = false;
		return;
	}

	// Escape - close popup
	if ( e.which === 27 ) {
		this.removePopup();
		shouldAbort.abort = true;
		return;
	}

	// Down key
	if ( e.which === 40 ) {
		this.navigateThroughResults( 'down' );
		shouldAbort.abort = true;
		return;
	}

	// Up key
	if ( e.which === 38 ) {
		this.navigateThroughResults( 'up' );
		shouldAbort.abort = true;
		return;
	}

	shouldAbort.abort = false;
};

bs.extendedSearch.Autocomplete.prototype.onValueChanged = function () {
	if ( this.searchBar.value === '' ) {
		return this.onEmptyFocus();
	}
	this.performSearch( this.searchBar.value );
};

bs.extendedSearch.Autocomplete.prototype.onSubmit = function ( e ) {
	e.preventDefault();
	// If no result is selected, or URI cannot be retrieved, submit to SearchCenter
	if ( !this.navigateToSelectedPopupItem() ) {
		$( this.searchBar.$searchForm ).off( 'submit' );
		// Set lookup object to be submitted
		this.setLookupToSubmit();

		$( this.searchBar.$searchForm ).submit(); // eslint-disable-line no-jquery/no-event-shorthand
	}
};

bs.extendedSearch.Autocomplete.prototype.onEmptyFocus = function () {
	this.removePopup();
	if ( !this.searchBar.showRecentlyFound ) {
		return;
	}

	bs.extendedSearch._getRecentlyFound().done( ( response ) => {
		if ( response.suggestions.length === 0 ) {
			return;
		}
		this.searchBar.$searchBox.attr( 'aria-expanded', true );
		this.searchBar.$searchBox.attr( 'aria-controls', this.searchBar.$searchBoxWrapper.attr( 'id' ) + '-results' );
		this.searchBar.$searchBox.attr( 'aria-autocomplete', 'list' );
		this.makePopup(
			response.suggestions,
			mw.msg( 'bs-extendedsearch-recently-found-header' )
		);
	} );
};

bs.extendedSearch.Autocomplete.prototype.onFocusOut = function ( e ) {
	if (
		this.searchBar.$searchContainer[ 0 ] &&
		(
			$.contains( this.searchBar.$searchContainer[ 0 ], e.currentTarget ) ||
				this.searchBar.$searchContainer[ 0 ] === e.currentTarget
		)
	) {
		return;
	}
	this.removePopup();
};

bs.extendedSearch.Autocomplete.prototype.onWindowClick = function ( e ) {
	if ( this.searchBar.$searchContainer[ 0 ] && $.contains( this.searchBar.$searchContainer[ 0 ], e.target ) ) {
		return;
	}
	this.removePopup();
};

bs.extendedSearch.Autocomplete.prototype.focusSearchBox = function () {
	this.searchBar.$searchBox.focus(); // eslint-disable-line no-jquery/no-event-shorthand
};

bs.extendedSearch.Autocomplete.prototype.removePopup = function () {
	if ( !this.popup ) {
		return;
	}

	this.searchBar.$searchContainer.find( this.popup.$element ).remove();
	this.popup = null;
	this.searchBar.suppressQuietSubpage( 'restore' );
};

bs.extendedSearch.Autocomplete.prototype.setLookupToSubmit = function () {
	// Make lookup and fill it with values from searchBar
	const lookup = new bs.extendedSearch.Lookup( this.lookupConfig );
	const hasContext = lookup.hasOwnProperty( 'context' );
	let queryString = this.searchBar.value;
	if ( this.searchBar.mainpage ) {
		queryString = this.searchBar.mainpage + '/' + queryString;
	}
	if ( this.hasNamespaceSet( this.searchBar ) ) {
		if ( this.searchBar.namespace.id !== 0 ) {
			queryString = this.searchBar.namespace.text + ':' + queryString;
		}
		if ( !hasContext ) {
			lookup.addTermsFilter( 'namespace', this.searchBar.namespace.id );
		}
	}

	lookup.setQueryString( queryString );

	// Create new hidden input and set its value to the lookup
	const $lookupField = $( '<input>' ).attr( 'type', 'hidden' ).attr( 'name', 'q' );
	$lookupField.val( JSON.stringify( lookup ) );

	// Add the field to the form to be submitted
	this.searchBar.$searchForm.append( $lookupField );
};

bs.extendedSearch.Autocomplete.prototype.hasNamespaceSet = function () {
	return this.searchBar.namespace &&
		this.searchBar.namespace.constructor === Object &&
		this.searchBar.namespace.hasOwnProperty( 'id' ) &&
		this.searchBar.namespace.hasOwnProperty( 'text' );
};

bs.extendedSearch.Autocomplete.prototype.navigateThroughResults = function ( direction ) {
	if ( !this.popup ) {
		return;
	}
	const result = this.popup.changeCurrent( direction );
	const $result = $( result );
	if ( $result.length === 1 ) {
		const title = $result.find( 'a' ).attr( 'data-title' );
		if ( title ) {
			this.searchBar.resetValue();
			this.searchBar.setValue( title );
			$result.attr( 'title', title );
		}
	}
	this.searchBar.$searchBox.attr( 'aria-activedescendant', $result.attr( 'id' ) );
};

bs.extendedSearch.Autocomplete.prototype.navigateToSelectedPopupItem = function () {
	if ( !this.popup ) {
		return false;
	}
	return this.popup.navigateToSelectedItem();
};

bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY = 'secondary';
bs.extendedSearch.Autocomplete.AC_RANK_PRIMARY = 'primary';
