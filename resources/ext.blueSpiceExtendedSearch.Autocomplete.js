( function( mw, $, bs, d, undefined ){
	//If user has navigated using arrows to a result,
	//we don't want form to be submited, user should be navigate to that page
	function onSubmit( e ) {
		e.preventDefault();
		var overrideSubmitting = bs.extendedSearch.Autocomplete.navigateToResultPage();
		//If no result is selected, or URI cannot be retieved, proceed with normal submit
		if( !overrideSubmitting ) {
			$( this ).submit();
		}
	}

	function onKeyUp( e ) {
		var value = e.target.value;

		//Escape - close popup
		if( e.which == 27 ) {
			this.removePopup();
			return;
		}

		//Down key
		if( e.which == 40 ) {
			this.navigateThroughResults( 'down' );
			return;
		}

		//Up key
		if( e.which == 38 ) {
			this.navigateThroughResults( 'up' );
			return;
		}

		if( this.valueBefore == '' && value == '' && e.which == 8 ) {
			//Backspacing on empty field
			this.removeNamespacePill( true );
			this.toggleClearButton( value );
		}

		if( this.valueBefore == value ) {
			return;
		}

		this.toggleClearButton( value );

		this.removePopup();
		this.showPopup( value );
	}

	function onKeyDown( e ) {
		this.valueBefore = e.target.value;
	}

	//Close popup on click outside of it
	function onWindowClick( e ) {
		if( $.contains( this.$searchContainer[0], e.target ) ) {
			return;
		}
		this.removePopup();
	}

	//Clear all search params
	function onClearSearch( e ) {
		this.$searchBox.val( '' );
		this.removeNamespacePill( true );
		this.removePopup();
		this.toggleClearButton( '' );
	}

	function _init( cfg ) {
		cfg = cfg || {};
		this.mobile = cfg.mobile || false;

		this.$searchContainer = $( '#' + cfg.cntId );
		this.$searchForm = this.$searchContainer.find( 'form' );
		this.$searchBox = $( '#' + cfg.inputId );
		this.$searchButton = this.$searchForm.find( 'button' );

		this.$searchBoxWrapper = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-wrapper' );

		//Wrap search box input in another div to make it sizable when pill is added
		this.$searchBoxWrapper.attr( 'style', 'width: ' + this.$searchBox.outerWidth() + 'px;' );
		this.$searchBox.attr( 'style' , 'display: table-cell; width: 100%;' );
		this.$searchBox.wrap( this.$searchBoxWrapper );

		//Wire the events
		this.$searchForm.one( 'submit', onSubmit );
		this.$searchBox.on( 'keydown', onKeyDown.bind( this ) );
		this.$searchBox.on( 'keyup', onKeyUp.bind( this ) );
		$( window ).on( 'click', onWindowClick.bind( this ) );
	}

	function _getPopupWidth() {
		var searchBoxWidth = parseInt( this.$searchBoxWrapper.outerWidth() );
		var searchButtonWidth = parseInt( this.$searchButton.outerWidth() );

		return searchBoxWidth + searchButtonWidth;
	}

	var autocompleteConfig = mw.config.get( 'bsgESAutocompleteConfig' );

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
			searchTerm: this.value,
			namespaceId: this.namespace.id || 0,
			displayLimits: autocompleteConfig["DisplayLimits"],
			mobile: this.mobile
		} );

		this.popup.$element.attr( 'style',
			'top:' + this.$searchBox.outerHeight() + 'px;' +
			'width:' + this.getPopupWidth() + 'px;'
		);

		this.popup.$element.insertAfter( $( '.bs-extendedsearch-autocomplete-wrapper' ) );
	}

	function _removePopup() {
		if( !this.popup ) {
			return;
		}

		this.$searchContainer.find( this.popup.$element ).remove();
		this.popup = null;
	}

	function _getSuggestions( value ) {
		this.detectNamespace( value );

		if( this.value.length < 3 ) {
			return;
		}

		var lookup = new bs.extendedSearch.Lookup();
		this.suggestField = autocompleteConfig['SuggestField'];

		lookup.addAutocompleteSuggest( this.suggestField, this.value );
		lookup.setAutocompleteSuggestSize(
			this.suggestField,
			autocompleteConfig['DisplayLimits']['primary']
		);

		//Adding another field that retrieves fuzzy results
		//It must be separete not to mess with main suggestions. It retrieves
		//"see also" suggestions.
		//We limit it size of secondary results, but i am not sure if fuzzy (non-matches)
		//will always be retrieved first. In my tests yes, but i couldnt find any documentation
		//confirming or disproving it
		lookup.addAutocompleteSuggest( this.suggestField, this.value, this.suggestField + '_fuzzy' );
		lookup.addAutocompleteSuggestFuzziness( this.suggestField + '_fuzzy', 2 );
		lookup.setAutocompleteSuggestSize(
			this.suggestField + '_fuzzy',
			autocompleteConfig['DisplayLimits']['secondary']
		);

		queryData = {
			q: JSON.stringify( lookup ),
			searchData: JSON.stringify( {
				namespace: this.namespace.id || 0,
				value: this.value
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

	//Grade-A programming
	function _detectNamespace( value ) {
		if( !this.namespaces ) {
			this.namespaces = bs.extendedSearch.utils.getNamespacesList();
		}

		var parts = value.split( ':' );
		if( parts.length == 1 ) {
			this.namespace = this.namespace || {};
			this.value = value;
			return;
		}
		if( parts.length == 2 && parts[1] === '' ) {
			this.namespace = {};
			this.value = '';
			return;
		}

		var newNamespace = parts.shift();
		if( newNamespace.toLowerCase() in this.namespaces ) {
			newNamespace = {
				id: this.namespaces[newNamespace.toLowerCase()],
				text: newNamespace,
				values: bs.extendedSearch.utils.getNamespaceNames( this.namespaces[newNamespace.toLowerCase()] )
			}
		} else {
			this.namespace = {};
			this.value = value;
			return;
		}

		if( newNamespace.id !== this.namespace.id ) {
			this.namespace = newNamespace;
			this.value = parts.shift();
			this.generateNamespacePill();
		}
	}

	function _generateNamespacePill() {
		this.removeNamespacePill();

		this.$pill = $( '<span>' ).addClass( 'bs-extendedsearch-autocomplete-pill' ).html( this.namespace.text );
		this.$searchBox.before( this.$pill );
		this.$searchBox.val( this.value );
	}

	function _removeNamespacePill( clearNamespace ) {
		clearNamespace = clearNamespace || false;

		if( clearNamespace ) {
			this.namespace = '';
		}

		this.$searchContainer.find( '.bs-extendedsearch-autocomplete-pill' ).remove();
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

	//Clear button handling
	function _addClearButton() {
		if( this.$searchContainer.find( '.bs-extendedsearch-autocomplete-clear' ).length > 0 ) {
			return;
		}

		var clearButton = new OO.ui.ButtonWidget( {
			indicator: 'clear',
			framed: false
		} );

		clearButton.$element.addClass( 'bs-extendedsearch-autocomplete-clear' );
		clearButton.$element.on( 'click', onClearSearch.bind( this ) );
		clearButton.$element.insertAfter( this.$searchBox );
		this.$searchBox.addClass( 'clear-present' );
	}

	function _removeClearButton() {
		this.$searchContainer.find( '.bs-extendedsearch-autocomplete-clear' ).remove();
		this.$searchBox.removeClass( 'clear-present' );
	}

	function _toggleClearButton( value ) {
		var pillPresent =
			this.$searchContainer.find( '.bs-extendedsearch-autocomplete-pill' ).length != 0;

		if( value || pillPresent ) {
			this.addClearButton();
		} else {
			this.removeClearButton();
		}
	}

	bs.extendedSearch.Autocomplete = {
		init: _init,
		showPopup: _showPopup,
		makePopup: _makePopup,
		removePopup: _removePopup,
		getSuggestions: _getSuggestions,
		detectNamespace: _detectNamespace,
		generateNamespacePill: _generateNamespacePill,
		removeNamespacePill: _removeNamespacePill,
		getIconPath: _getIconPath,
		navigateThroughResults: _navigateThroughResults,
		navigateToResultPage: _navigateToResultPage,
		getPopupWidth: _getPopupWidth,
		addClearButton: _addClearButton,
		removeClearButton: _removeClearButton,
		toggleClearButton: _toggleClearButton
	}

	//MobileFrontend is required to make this decision
	//on load-time, it is not used, so we init correct type here
	var $desktopSearchBox = $( '#bs-extendedsearch-box' );
	var $mobileSearchBox = $( '#bs-extendedsearch-mobile-box' );

	if( $desktopSearchBox.is( ':visible' ) ) {
		bs.extendedSearch.Autocomplete.init( {
			cntId: 'bs-extendedsearch-box',
			inputId: 'bs-extendedsearch-input',
			mobile: false
		} );
	} else if ( $mobileSearchBox.is( ':visible' ) ) {
		bs.extendedSearch.Autocomplete.init( {
			cntId: 'bs-extendedsearch-mobile-box',
			inputId: 'bs-extendedsearch-mobile-input',
			mobile: true
		} );
	}
} )( mediaWiki, jQuery, blueSpice, document );