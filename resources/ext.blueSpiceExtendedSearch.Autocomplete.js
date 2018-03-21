( function( mw, $, bs, d, undefined ){
	var $searchContainer = $( '#bs-extendedsearch-box' );
	var $searchForm = $searchContainer.find( 'form' );
	var $searchBox = $( '#bs-extendedsearch-input' );
	var $searchButton = $searchForm.find( 'button' );

	var $searchBoxWrapper = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-wrapper' );
	$searchBoxWrapper.attr( 'style', 'width: ' + $searchBox.outerWidth() + 'px;' );
	$searchBox.attr( 'style' , 'display: table-cell; width: 100%;' );
	$searchBox.wrap( $searchBoxWrapper );

	//If user has navigated using arrows to a result,
	//we don't want form to be submited, user should be navigate to that page
	$searchForm.one( 'submit', function( e ) {
		e.preventDefault();
		var overrideSubmitting = bs.extendedSearch.Autocomplete.navigateToResultPage();
		//If no result is selected, or URI cannot be retieved, proceed with normal submit
		if( !overrideSubmitting ) {
			$( this ).submit();
		}
	} );

	var valueBefore = '';
	$searchBox.on( 'keydown', function( e ) {
		valueBefore = e.target.value;
	} );

	$searchBox.on( 'keyup', function( e ) {
		var value = e.target.value;

		//Escape - close popup
		if( e.which == 27 ) {
			bs.extendedSearch.Autocomplete.removePopup();
			return;
		}

		//Down key
		if( e.which == 40 ) {
			bs.extendedSearch.Autocomplete.navigateThroughResults( 'down' );
			return;
		}

		//Up key
		if( e.which == 38 ) {
			bs.extendedSearch.Autocomplete.navigateThroughResults( 'up' );
			return;
		}

		if( valueBefore == '' && value == '' && e.which == 8 ) {
			//Backspacing on empty field
			bs.extendedSearch.Autocomplete.removeNamespacePill( true );
		}

		if( valueBefore == value ) {
			return;
		}

		bs.extendedSearch.Autocomplete.removePopup();
		bs.extendedSearch.Autocomplete.showPopup( value );
	} );

	function getPopupWidth() {
		var searchBoxWidth = parseInt( $searchBoxWrapper.outerWidth() );
		var searchButtonWidth = parseInt( $searchButton.outerWidth() );

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
			displayLimits: autocompleteConfig["DisplayLimits"]
		} );

		this.popup.$element.attr( 'style',
			'top:' + $searchBox.outerHeight() + 'px;' +
			'width:' + getPopupWidth() + 'px;'
		);

		this.popup.$element.insertAfter( $( '.bs-extendedsearch-autocomplete-wrapper' ) );
	}

	function _removePopup() {
		if( !this.popup ) {
			return;
		}

		$searchContainer.find( this.popup.$element ).remove();
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
		$searchBox.before( this.$pill );
		$searchBox.val( this.value );
	}

	function _removeNamespacePill( clearNamespace ) {
		clearNamespace = clearNamespace || false;

		if( clearNamespace ) {
			this.namespace = '';
		}

		$searchContainer.find( '.bs-extendedsearch-autocomplete-pill' ).remove();
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
		showPopup: _showPopup,
		makePopup: _makePopup,
		removePopup: _removePopup,
		getSuggestions: _getSuggestions,
		detectNamespace: _detectNamespace,
		generateNamespacePill: _generateNamespacePill,
		removeNamespacePill: _removeNamespacePill,
		getIconPath: _getIconPath,
		navigateThroughResults: _navigateThroughResults,
		navigateToResultPage: _navigateToResultPage
	}

} )( mediaWiki, jQuery, blueSpice, document );