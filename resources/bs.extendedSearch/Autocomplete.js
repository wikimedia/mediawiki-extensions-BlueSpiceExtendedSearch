( function( mw, $, bs, d, undefined ){
	function _init( cfg ) {
		cfg = cfg || {};
		this.searchBar = cfg.searchBar;
		this.compact = cfg.compact || false;
		this.lookupConfig = cfg.lookupConfig || {};
		this.autocompleteConfig = cfg.autocompleteConfig;
		this.sourceIcons = cfg.sourceIcons;
		this.api = new mw.Api();

		this.mainpage = '';

		//Wire the events
		this.searchBar.$searchForm.on( 'submit', this.onSubmit.bind( this ) );
		this.searchBar.on( 'beforeValueChanged', this.beforeValueChanged.bind( this ) );
		this.searchBar.on( 'valueChanged', this.onValueChanged.bind( this ) );
		this.searchBar.on( 'clearSearch', this.onClearSearch.bind( this ) );
		this.searchBar.on( 'emptyFocus', this.onEmptyFocus.bind( this ) );
		this.searchBar.$searchForm.on( 'focusout', this.onFocusOut.bind( this ) );
		$( window ).on( 'click', this.onWindowClick.bind( this ) );
		this._initComplete = true;
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
		if( _hasNamespaceSet( this.searchBar ) ) {
			if ( this.searchBar.namespace.id !== 0 ) {
				queryString = this.searchBar.namespace.text + ':' + queryString;
			}

			lookup.addTermsFilter( 'namespace_text', this.searchBar.namespace.text );
		}

		lookup.setQueryString( queryString );

		//Create new hidden input and set its value to the lookup
		var $lookupField = $( '<input>' ).attr( 'type', 'hidden' ).attr( 'name', 'q' );
		$lookupField.val( JSON.stringify( lookup ) );

		//Add the field to the form to be submitted
		this.searchBar.$searchForm.append( $lookupField );
	}

	function _hasNamespaceSet( searchBar ) {
		return searchBar.namespace &&
			searchBar.namespace.constructor === Object &&
			searchBar.namespace.hasOwnProperty( 'id' ) &&
			searchBar.namespace.hasOwnProperty( 'text' );
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
		if ( this.searchBar.value === '' ) {
			return this.onEmptyFocus();
		}
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


	//Close popup on tab to new button
	function _onFocusOut( e ) {
		if( this.searchBar.$searchContainer[0] &&
			$.contains( this.searchBar.$searchContainer[0], e.relatedTarget ) ) {
			return;
		}
		this.removePopup();
	}

	function _onEmptyFocus() {
		this.removePopup();
		if ( !this.searchBar.showRecentlyFound ) {
			return;
		}
		bs.extendedSearch._getRecentlyFound().done( function( response ) {
			if ( response.suggestions.length === 0 ) {
				return;
			}
			this.makePopup(
				response.suggestions,
				{ creatable: false },
				mw.msg( 'bs-extendedsearch-recently-found-header' ),
				mw.msg( 'bs-extendedsearch-recently-found-header-aria', response.suggestions.length )
			);
		}.bind( this ) );
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

	function _makePopup( suggestions, pageCreateInfo, headerText, ariaAnnouncerText ) {
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
			compact: this.compact,
			quietSubpage: this.searchBar.getMasterFilterPage(),
			autocomplete: this,
			headerText: headerText
		};

		this.popup = new bs.extendedSearch.AutocompletePopup( popupCfg );
		this.popup.connect( this, {
			quietSubpageRemoved: function() {
				this.searchBar.suppressQuietSubpage( 'suppress' );
				this.searchBar.changeValue( this.searchBar.$searchBox.val() );
			},
			closePopup: function () {
				this.removePopup();
			}
		} );

		this.popup.$element.css( 'top', this.searchBar.$searchBox.outerHeight() + 'px' );
		this.popup.$element.addClass( 'searchbar-autocomplete-results' );
		var wrapperId = this.searchBar.$searchBoxWrapper.attr( 'id' );
		this.popup.$element.insertAfter( $( '#' + wrapperId ) );
		if ( ariaAnnouncerText ) {
			this.popup.announce( ariaAnnouncerText );
		}

		bs.extendedSearch._registerTrackableLinks();
		this.searchBar.suppressQuietSubpage( 'arm' );
	}

	function _removePopup() {
		if( !this.popup ) {
			return;
		}

		this.searchBar.$searchContainer.find( this.popup.$element ).remove();
		this.popup = null;
		this.searchBar.suppressQuietSubpage( 'restore' );
	}

	function _getSuggestions() {
		this.searchBar.setPending();
		var lookup = new bs.extendedSearch.Lookup( this.lookupConfig );
		this.suggestField = this.autocompleteConfig['SuggestField'];

		// We set the AC query term to lowercase always, as that will work with tokenizer
		lookup.setMultiMatchQuery( this.suggestField, this.searchBar.value.toLowerCase() );
		// We are setting excessive size here due to how autocomplete ranking works,
		// It will not focus on returning "best" matches necessarily, but rather
		// the first ones that match the query. This is why we need to get a lot of results,
		// to be able to do our own ranking on a larger set of results.
		// Difference in performance: 10 results = 0.1s, 100 results = 0.3s
		lookup.setSize( 50 );
		if( $.isEmptyObject( this.searchBar.namespace ) === false ) {
			lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
		}

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
			me.searchBar.clearPending();
			$( d ).trigger( 'BSExtendedSearchAutocompleteSuggestionsRetrieved', [ response.suggestions || [] ] );
			me.makePopup(
				response.suggestions,
				response.page_create_info
			);

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
				bs.extendedSearch._registerTrackableLinks();
			} );
		} ).fail( function() {
			this.searchBar.clearPending();
		}.bind( this ) );
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

		lookup.setBoolMatchQueryString( suggestField, this.searchBar.value );
		if( this.searchBar.namespace.id ) {
			if( primarySuggestions.length === 0 ) {
				//If we are in NS and there are no primary results, look for fuzzy in this NS
				lookup.setBoolMatchQueryFuzziness( suggestField, 2, { prefix_length: 1 } );
				lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
			} else {
				//Search for non-fuzzy matches in other namespaces
				lookup.addBoolMustNotTerms( 'namespace', this.searchBar.namespace.id );
				lookup.setSize( this.autocompleteConfig['DisplayLimits']['secondary'] );
			}
		} else {
			lookup.setBoolMatchQueryFuzziness( suggestField, 2, { prefix_length: 1 } );
			//Do not find non-fuzzy matches
			lookup.addBoolMustNotTerms( suggestField, this.searchBar.value );
			lookup.setSize( this.autocompleteConfig['DisplayLimits']['secondary'] );
		}

		return this.runLookup( lookup, {
			secondaryRequestData: JSON.stringify( {
				primary_suggestions: primarySuggestions.map( function( suggestion ) {
					return suggestion.id || '';
				} )
			})
		});
	}

	function _runLookup( lookup, data ) {
		data = data || {};

		var queryData = $.extend( {
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
		if( type.toLowerCase() in this.sourceIcons ) {
			return scriptPath + '/' + this.sourceIcons[type];
		}
		return scriptPath + '/' + this.sourceIcons['default'];
	}

	function _navigateThroughResults( direction ) {
		if( !this.popup || this.searchBar.mobile ) {
			return;
		}
		var result = this.popup.changeCurrent( direction );
		var $result = $( result );
		if ( $result.length === 1 ) {
			var title = $result.find( 'a' ).attr( 'data-bs-title' );
			if ( title ) {
				this.searchBar.resetValue();
				this.searchBar.setValue( title );
			}
		}
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
		if( suggestions.length === 0 ) {
			return;
		}

		if( !this.popup ) {
			return;
		}

		this.popup.addSecondary( suggestions );
	}

	function _focusSearchBox() {
		if ( this._initComplete === false ) {
			return;
		}
		this.searchBar.$searchBox.focus();
	}

	bs.extendedSearch.Autocomplete = function() {
		this._initComplete = false;
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
	bs.extendedSearch.Autocomplete.prototype.onEmptyFocus = _onEmptyFocus;
	bs.extendedSearch.Autocomplete.prototype.onFocusOut = _onFocusOut;
	bs.extendedSearch.Autocomplete.prototype.onSubmit = _onSubmit;
	bs.extendedSearch.Autocomplete.prototype.beforeValueChanged = _beforeValueChanged;
	bs.extendedSearch.Autocomplete.prototype.onWindowClick = _onWindowClick;
	bs.extendedSearch.Autocomplete.prototype.setLookupToSubmit = _setLookupToSubmit;
	bs.extendedSearch.Autocomplete.prototype.focusSearchBox = _focusSearchBox;
	bs.extendedSearch.Autocomplete.AC_RANK_TOP = 'top';
	bs.extendedSearch.Autocomplete.AC_RANK_SECONDARY = 'secondary';
	bs.extendedSearch.Autocomplete.AC_RANK_NORMAL = 'normal';

} )( mediaWiki, jQuery, blueSpice, document );
