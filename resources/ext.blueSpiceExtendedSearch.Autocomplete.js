( function( mw, $, bs, d, undefined ){
	function _init() {
		this.searchBar = new bs.extendedSearch.SearchBar();

		this.autocompleteConfig = mw.config.get( 'bsgESAutocompleteConfig' );

		//Wire the events
		this.searchBar.$searchForm.one( 'submit', this.onSubmit );
		this.searchBar.beforeValueChanged = this.beforeValueChanged;
		this.searchBar.onValueChanged = this.onValueChanged;
		this.searchBar.onClearSearch = this.onClearSearch;
		$( window ).on( 'click', onWindowClick.bind( this ) );

		this.api = new mw.Api();
	}

	//If user has navigated using arrows to a result,
	//we don't want form to be submited, user should be navigate to that page
	function _onSubmit( e ) {
		e.preventDefault();
		var overrideSubmitting = bs.extendedSearch.Autocomplete.navigateToResultPage();
		//If no result is selected, or URI cannot be retieved, proceed with normal submit
		if( !overrideSubmitting ) {
			$( this ).submit();
		}
	}

	function _beforeValueChanged( e ) {
		if( e.type != 'keyup' ) {
			return;
		}

		//Escape - close popup
		if( e.which == 27 ) {
			bs.extendedSearch.Autocomplete.removePopup();
			return false;
		}

		//Down key
		if( e.which == 40 ) {
			bs.extendedSearch.Autocomplete.navigateThroughResults( 'down' );
			return false;
		}

		//Up key
		if( e.which == 38 ) {
			bs.extendedSearch.Autocomplete.navigateThroughResults( 'up' );
			return false;
		}

		return true;
	}

	function _onValueChanged() {
		bs.extendedSearch.Autocomplete.removePopup();
		bs.extendedSearch.Autocomplete.showPopup( this.value );
	}

	//Close popup on click outside of it
	function onWindowClick( e ) {
		if( $.contains( this.searchBar.$searchContainer[0], e.target ) ) {
			return;
		}
		bs.extendedSearch.Autocomplete.removePopup();
	}

	//Clear all search params
	function _onClearSearch() {
		bs.extendedSearch.SearchBar.prototype.onClearSearch.call( this );
		bs.extendedSearch.Autocomplete.removePopup();
	}

	function _getPopupWidth() {
		var searchBoxWidth = parseInt( this.searchBar.$searchBoxWrapper.outerWidth() );
		var searchButtonWidth = parseInt( this.searchBar.$searchButton.outerWidth() );

		return searchBoxWidth + searchButtonWidth;
	}

	function _showPopup( value ) {
		this.getSuggestions( value );
	}

	function _makePopup( suggestions, pageCreateInfo ) {
		if( this.popup ) {
			this.removePopup();
		}

		var popupCfg = {
			data: suggestions,
			searchTerm: this.searchBar.value,
			namespaceId: this.searchBar.namespace.id || 0,
			displayLimits: this.autocompleteConfig["DisplayLimits"],
			mobile: this.searchBar.mobile,
			pageCreateInfo: pageCreateInfo
		};

		this.popup = new bs.extendedSearch.AutocompletePopup( popupCfg );

		this.popup.$element.attr( 'style',
			'top:' + this.searchBar.$searchBox.outerHeight() + 'px;' +
			'width:' + this.getPopupWidth() + 'px;'
		);

		this.popup.$element.insertAfter( $( '.bs-extendedsearch-searchbar-wrapper' ) );
	}

	function _removePopup() {
		if( !this.popup ) {
			return;
		}

		this.searchBar.$searchContainer.find( this.popup.$element ).remove();
		this.popup = null;
	}

	function _getSuggestions() {
		if( this.searchBar.value.length < 3 ) {
			return;
		}

		var lookup = new bs.extendedSearch.Lookup();
		this.suggestField = this.autocompleteConfig['SuggestField'];

		lookup.setBoolMatchQueryString( this.suggestField, this.searchBar.value );
		if( this.searchBar.namespace.id ) {
			lookup.addTermFilter( 'namespace', this.searchBar.namespace.id );
		}
		lookup.setSize( this.autocompleteConfig['DisplayLimits']['primary'] );

		var me = this;
		this.runLookup( lookup ).done( function( response ) {
			me.makePopup( response.suggestions, response.page_create_info );

			me.getSecondaryResults().done( function( response ) {
				me.addSecondaryToPopup( response.suggestions );
			} );
		} );
	}

	/**
	 * After main query has ran and popup is shown,
	 * get the secondary results
	 *
	 * @returns {Deferred}
	 */
	function _getSecondaryResults() {
		var lookup = new bs.extendedSearch.Lookup();
		var suggestField = this.autocompleteConfig['SuggestField'];

		lookup.setBoolMatchQueryString( this.suggestField, this.searchBar.value );
		if( this.searchBar.namespace.id ) {
			//Search for matches in other namespaces
			lookup.addBoolMustNotTerms( 'namespace', this.searchBar.namespace.id );
			lookup.setSize( this.autocompleteConfig['DisplayLimits']['secondary'] );
		} else {
			lookup.setBoolMatchQueryFuzziness( this.suggestField, 2, { prefix_length: 1 } );
			//Do not find non-fuzzy matches
			lookup.addBoolMustNotTerms( this.suggestField, this.searchBar.value );
			lookup.setSize( this.autocompleteConfig['DisplayLimits']['secondary'] );
		}

		return this.runLookup( lookup );
	}

	function _runLookup( lookup ) {
		var dfd = $.Deferred();
		queryData = {
			q: JSON.stringify( lookup ),
			searchData: JSON.stringify( {
				namespace: this.searchBar.namespace.id || 0,
				value: this.searchBar.value
			} )
		}

		this.api.abort();

		this.api.get( $.extend(
			queryData,
			{
				'action': 'bs-extendedsearch-autocomplete'
			}
		) )
		.done( function( response ) {
			dfd.resolve( response );
		} )
		.fail( function( ) {
			dfd.reject();
		});

		return dfd;
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
		if( !this.popup ) {
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

	bs.extendedSearch.Autocomplete = {
		init: _init,
		showPopup: _showPopup,
		makePopup: _makePopup,
		removePopup: _removePopup,
		getSuggestions: _getSuggestions,
		getSecondaryResults: _getSecondaryResults,
		runLookup: _runLookup,
		addSecondaryToPopup: _addSecondaryToPopup,
		getIconPath: _getIconPath,
		navigateThroughResults: _navigateThroughResults,
		navigateToResultPage: _navigateToResultPage,
		getPopupWidth: _getPopupWidth,
		onClearSearch: _onClearSearch,
		onValueChanged: _onValueChanged,
		onSubmit: _onSubmit,
		beforeValueChanged: _beforeValueChanged,

		AC_RANK_TOP: 'top',
		AC_RANK_PRIMARY: 'primary',
		AC_RANK_SECONDARY: 'secondary'
	}

	bs.extendedSearch.Autocomplete.init();

} )( mediaWiki, jQuery, blueSpice, document );