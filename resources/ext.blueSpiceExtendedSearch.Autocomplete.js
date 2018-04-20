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

	function _makePopup( suggestions ) {
		if( suggestions.length === 0 ) {
			return;
		}

		if( this.popup ) {
			this.removePopup();
		}

		this.popup = new bs.extendedSearch.AutocompletePopup( {
			data: suggestions,
			searchTerm: this.searchBar.value,
			namespaceId: this.searchBar.namespace.id || 0,
			displayLimits: this.autocompleteConfig["DisplayLimits"],
			mobile: this.searchBar.mobile
		} );

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

		lookup.addAutocompleteSuggest( this.suggestField, this.searchBar.value );
		lookup.setAutocompleteSuggestSize(
			this.suggestField,
			this.autocompleteConfig['DisplayLimits']['primary']
		);

		//Adding another field that retrieves fuzzy results
		//It must be separete not to mess with main suggestions. It retrieves
		//"see also" suggestions.
		//We limit it size of secondary results, but i am not sure if fuzzy (non-matches)
		//will always be retrieved first. In my tests yes, but i couldnt find any documentation
		//confirming or disproving it
		lookup.addAutocompleteSuggest( this.suggestField, this.searchBar.value, this.suggestField + '_fuzzy' );
		lookup.addAutocompleteSuggestFuzziness( this.suggestField + '_fuzzy', 2 );
		lookup.setAutocompleteSuggestSize(
			this.suggestField + '_fuzzy',
			this.autocompleteConfig['DisplayLimits']['secondary']
		);

		queryData = {
			q: JSON.stringify( lookup ),
			searchData: JSON.stringify( {
				namespace: this.searchBar.namespace.id || 0,
				value: this.searchBar.value
			} )
		}

		var api = new mw.Api();
		api.abort();
		api.get( $.extend(
			queryData,
			{
				'action': 'bs-extendedsearch-autocomplete'
			}
		) )
		.done( function( response ) {
			bs.extendedSearch.Autocomplete.makePopup( response.suggestions );
		} );
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

	bs.extendedSearch.Autocomplete = {
		init: _init,
		showPopup: _showPopup,
		makePopup: _makePopup,
		removePopup: _removePopup,
		getSuggestions: _getSuggestions,
		getIconPath: _getIconPath,
		navigateThroughResults: _navigateThroughResults,
		navigateToResultPage: _navigateToResultPage,
		getPopupWidth: _getPopupWidth,
		onClearSearch: _onClearSearch,
		onValueChanged: _onValueChanged,
		onSubmit: _onSubmit,
		beforeValueChanged: _beforeValueChanged
	}

	bs.extendedSearch.Autocomplete.init();

} )( mediaWiki, jQuery, blueSpice, document );