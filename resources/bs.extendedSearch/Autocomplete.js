( function( mw, $, bs, d, undefined ){
	function _init( cfg ) {
		cfg = cfg || {};
		this.searchBar = cfg.searchBar;
		this.compact = cfg.compact || false;
		this.lookupConfig = cfg.lookupConfig || {};

		this.autocompleteConfig = mw.config.get( 'bsgESAutocompleteConfig' );

		this.api = new mw.Api();

		this.mainpage = '';

		//Wire the events
		this.searchBar.$searchForm.on( 'submit', this.onSubmit.bind( this ) );
		this.searchBar.on( 'beforeValueChanged', this.beforeValueChanged.bind( this ) );
		this.searchBar.on( 'valueChanged', this.onValueChanged.bind( this ) );
		this.searchBar.on( 'clearSearch', this.onClearSearch.bind( this ) );
		$( window ).on( 'click', this.onWindowClick.bind( this ) );
	}

	//If user has navigated using arrows to a result,
	//we don't want form to be submited, user should be navigate to that page
	function _onSubmit( e ) {
		e.preventDefault();
		var overrideSubmitting = this.navigateToResultPage();
		//If no result is selected, or URI cannot be retieved, submit to SearchCenter
		if( !overrideSubmitting ) {
			$( this.searchBar.$searchForm ).off( 'submit' );
			//set lookup object to be submited
			this.setLookupToSubmit();

			$( this.searchBar.$searchForm ).submit();
		}
	}

	function _setLookupToSubmit() {
		//Make lookup and fill it with values from searchBar
		var lookup = new bs.extendedSearch.Lookup( this.lookupConfig );
		var queryString = this.searchBar.value;
		if( this.searchBar.mainpage ) {
			queryString = this.searchBar.mainpage + '/' + queryString;
		}

		lookup.setQueryString( queryString );
		if( this.searchBar.namespace.id ) {
			lookup.addTermsFilter( 'namespace_text', this.searchBar.namespace.text );
		}

		//Create new hidden input and set its value to the lookup
		var $lookupField = $( '<input>' ).attr( 'type', 'hidden' ).attr( 'name', 'q' );
		$lookupField.val( JSON.stringify( lookup ) );

		//Add the field to the form to be submitted
		this.searchBar.$searchForm.append( $lookupField );
	}

	function _beforeValueChanged( e, shouldAbort ) {
		if( e.type != 'keyup' ) {
			shouldAbort.abort = false;
			return;
		}

		//Escape - close popup
		if( e.which == 27 ) {
			this.removePopup();
			shouldAbort.abort = true;
			return;
		}

		//Down key
		if( e.which == 40 ) {
			this.navigateThroughResults( 'down' );
			shouldAbort.abort = true;
			return;
		}

		//Up key
		if( e.which == 38 ) {
			this.navigateThroughResults( 'up' );
			shouldAbort.abort = true;
			return;
		}

		//Left key
		if( e.which == 37 ) {
			this.navigateThroughResults( 'left' );
			shouldAbort.abort = true;
			return;
		}

		//Right key
		if( e.which == 39 ) {
			this.navigateThroughResults( 'right' );
			shouldAbort.abort = true;
			return;
		}

		shouldAbort.abort = false;
	}

	function _onValueChanged() {
		this.removePopup();
		this.showPopup( this.searchBar.value );
	}

	//Close popup on click outside of it
	function _onWindowClick( e ) {
		if( this.searchBar.$searchContainer[0] && $.contains( this.searchBar.$searchContainer[0], e.target ) ) {
			return;
		}
		this.removePopup();
	}

	//Clear all search params
	function _onClearSearch() {
		this.removePopup();
	}

	function _getPopupWidth() {
		var searchBoxWidth = parseInt( this.searchBar.$searchBoxWrapper.outerWidth() );
		var searchButtonWidth = parseInt( this.searchBar.$searchButton.outerWidth() );

		return searchBoxWidth + searchButtonWidth;
	}

	function _showPopup( value ) {
		if( value ) {
			this.getSuggestions( value );
		}
	}

	function _makePopup( suggestions, pageCreateInfo ) {
		if( this.popup ) {
			this.removePopup();
		}

		var popupCfg = {
			searchForm: this.searchBar.$searchForm,
			data: suggestions,
			searchTerm: this.searchBar.value,
			namespaceId: this.searchBar.namespace.id || 0,
			displayLimits: this.autocompleteConfig["DisplayLimits"],
			mobile: this.searchBar.mobile,
			pageCreateInfo: pageCreateInfo,
			compact: this.compact
		};

		this.popup = new bs.extendedSearch.AutocompletePopup( popupCfg );

		this.popup.$element.css( 'top', this.searchBar.$searchBox.outerHeight() + 'px' );
		this.popup.$element.css( 'width', this.getPopupWidth() + 'px' );
		this.popup.$element.css( 'height', "600px" );

		var wrapperId = this.searchBar.$searchBoxWrapper.attr( 'id' );
		this.popup.$element.insertAfter( $( '#' + wrapperId ) );
	}

	function _removePopup() {
		if( !this.popup ) {
			return;
		}

		this.searchBar.$searchContainer.find( this.popup.$element ).remove();
		this.popup = null;
	}

	function _getSuggestions() {
		var lookup = new bs.extendedSearch.Lookup( this.lookupConfig );

		this.suggestField = this.autocompleteConfig['SuggestField'];

		lookup.setBoolMatchQueryString( this.suggestField, this.searchBar.value );
		if( $.isEmptyObject( this.searchBar.namespace ) == false ) {
			lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
		}
		var primaryCount = this.autocompleteConfig['DisplayLimits']['normal'] +
			this.autocompleteConfig['DisplayLimits']['top']
		lookup.setSize( primaryCount );

		if( this.searchBar.mainpage ) {
			var mainpage = this.searchBar.mainpage;
			var mainpageQuery = { regexp: {} };
			mainpageQuery.regexp['basename_exact'] = mainpage + "|" + mainpage + "/.*";
			var origMatch = lookup.query.bool.must;
			lookup.query.bool.must = [
				origMatch,
				mainpageQuery
			];

			// We dont want to search for subpages of a page with this name
			// in all namespaces. If ns is not set search in NS_MAIN
			if( $.isEmptyObject( this.searchBar.namespace )  ) {
				lookup.addTermFilter( 'namespace', 0 );
			}
		}

		var me = this;
		this.runLookup( lookup ).done( function( response ) {
			me.makePopup( response.suggestions, response.page_create_info );

			if( me.compact || me.searchBar.mobile ) {
				//In mobile and compact view there are only primary results
				//so no point in retrieving secondary
				return;
			}

			var primary = getSuggestionsByRank( response.suggestions, 'primary' );
			if( $.isEmptyObject( me.searchBar.namespace ) && primary.length > 0  ) {
				// No need to retrieve "other namespaces" suggestions,
				// and primary matches exist
				return;
			}

			var secondary = getSuggestionsByRank( response.suggestions, 'secondary' );
			if( secondary.length > 0 ) {
				me.addSecondaryToPopup( secondary );
				// There are secondary suggestions already in result set
				return;
			}

			me.getSecondaryResults( response.suggestions ).done( function( response ) {
				me.addSecondaryToPopup( response.suggestions );
			} );
		} );
	}

	function getSuggestionsByRank( suggestions, rank ) {
		var res = [];
		for( var i = 0; i < suggestions.length; i++ ) {
			var suggestion = suggestions[i];
			if ( suggestion['rank'] === rank ) {
				res.push( suggestion );
			}
		}
		return res;
	}

	/**
	 * After main query has ran and popup is shown,
	 * get the secondary results
	 *
	 * @returns {Deferred}
	 */
	function _getSecondaryResults( primarySuggestions ) {
		var lookup = new bs.extendedSearch.Lookup( this.lookupConfig );
		var suggestField = this.autocompleteConfig['SuggestField'];

		lookup.setBoolMatchQueryString( this.suggestField, this.searchBar.value );
		if( this.searchBar.namespace.id ) {
			if( primarySuggestions.length === 0 ) {
				//If we are in NS and there are no primary results, look for fuzzy in this NS
				lookup.setBoolMatchQueryFuzziness( this.suggestField, 2, { prefix_length: 1 } );
				lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
			} else {
				//Search for non-fuzzy matches in other namespaces
				lookup.addBoolMustNotTerms( 'namespace', this.searchBar.namespace.id );
				lookup.setSize( this.autocompleteConfig['DisplayLimits']['secondary'] );
			}
		} else {
			lookup.setBoolMatchQueryFuzziness( this.suggestField, 2, { prefix_length: 1 } );
			//Do not find non-fuzzy matches
			lookup.addBoolMustNotTerms( this.suggestField, this.searchBar.value );
			lookup.setSize( this.autocompleteConfig['DisplayLimits']['secondary'] );
		}

		return this.runLookup( lookup, {
			secondaryRequestData: JSON.stringify( {
				primary_suggestions: primarySuggestions
			})
		});
	}

	function _runLookup( lookup, data ) {
		data = data || {};

		queryData = $.extend( {
			q: JSON.stringify( lookup ),
			searchData: JSON.stringify( {
				namespace: this.searchBar.namespace.id || 0,
				value: this.searchBar.value,
				mainpage: this.searchBar.mainpage || ''
			} )
		}, data );
		this.api.abort();

		return this.api.get( $.extend(
			queryData,
			{
				'action': 'bs-extendedsearch-autocomplete'
			}
		) );
	}

	function _getIconPath( type ) {
		var scriptPath = mw.config.get( 'wgScriptPath' );
		var icons = mw.config.get( 'bsgESSourceIcons' );
		if( type in icons ) {
			return scriptPath + '/' + icons[type];
		}
		return scriptPath + '/' + icons['default'];
	}

	function _navigateThroughResults( direction ) {
		if( !this.popup || this.searchBar.mobile ) {
			return;
		}
		this.popup.changeCurrent( direction );
	}

	function _navigateToResultPage() {
		if( !this.popup ) {
			return false;
		}
		var uri = this.popup.getCurrentUri();

		if( !uri ) {
			return false;
		}

		window.location.href = uri;
		return true;
	}

	function _addSecondaryToPopup( suggestions ) {
		if( suggestions.length == 0 ) {
			return;
		}

		if( !this.popup ) {
			return;
		}

		this.popup.addSecondary( suggestions );
	}

	bs.extendedSearch.Autocomplete = function() {
	};

	bs.extendedSearch.Autocomplete.prototype.init = _init;
	bs.extendedSearch.Autocomplete.prototype.showPopup = _showPopup;
	bs.extendedSearch.Autocomplete.prototype.makePopup = _makePopup;
	bs.extendedSearch.Autocomplete.prototype.removePopup = _removePopup;
	bs.extendedSearch.Autocomplete.prototype.getSuggestions = _getSuggestions;
	bs.extendedSearch.Autocomplete.prototype.getSecondaryResults = _getSecondaryResults;
	bs.extendedSearch.Autocomplete.prototype.runLookup = _runLookup;
	bs.extendedSearch.Autocomplete.prototype.addSecondaryToPopup = _addSecondaryToPopup;
	bs.extendedSearch.Autocomplete.prototype.getIconPath = _getIconPath;
	bs.extendedSearch.Autocomplete.prototype.navigateThroughResults = _navigateThroughResults;
	bs.extendedSearch.Autocomplete.prototype.navigateToResultPage = _navigateToResultPage;
	bs.extendedSearch.Autocomplete.prototype.getPopupWidth = _getPopupWidth;
	bs.extendedSearch.Autocomplete.prototype.onClearSearch = _onClearSearch;
	bs.extendedSearch.Autocomplete.prototype.onValueChanged = _onValueChanged;
	bs.extendedSearch.Autocomplete.prototype.onSubmit = _onSubmit;
	bs.extendedSearch.Autocomplete.prototype.beforeValueChanged = _beforeValueChanged;
	bs.extendedSearch.Autocomplete.prototype.onWindowClick = _onWindowClick;
	bs.extendedSearch.Autocomplete.prototype.setLookupToSubmit = _setLookupToSubmit;
	bs.extendedSearch.Autocomplete.AC_RANK_TOP = 'top';
	bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY = 'secondary';
	bs.extendedSearch.Autocomplete.AC_RANK_NORMAL = 'normal';

} )( mediaWiki, jQuery, blueSpice, document );
