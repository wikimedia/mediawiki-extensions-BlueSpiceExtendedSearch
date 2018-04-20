( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.SearchBar = function( cfg ) {
		this.init( cfg );
	}

	bs.extendedSearch.SearchBar.prototype.init = function( cfg ) {
		cfg = cfg || {};

		this.mobile = cfg.mobile || false;
		this.value = '';
		this.namespace = {};

		this.useNamespacePills = true;
		if( cfg.useNamespacePills === false ) {
			this.useNamespacePills = false;
		}

		if( bs.extendedSearch.utils.isMobile() ) {
			this.mobile = true;
			cfg.cntId = cfg.cntId || 'bs-extendedsearch-mobile-box';
			cfg.inputId = cfg.inputId || 'bs-extendedsearch-mobile-input';
		} else {
			cfg.cntId = cfg.cntId || 'bs-extendedsearch-box';
			cfg.inputId = cfg.inputId || 'bs-extendedsearch-input';
		}

		this.$searchContainer = $( '#' + cfg.cntId );
		this.$searchForm = this.$searchContainer.find( 'form' );
		this.$searchBox = $( '#' + cfg.inputId );
		this.$searchButton = this.$searchForm.find( 'button' );

		this.$namespaceInput = $( '<input>' ).attr( 'type', 'hidden' ).attr( 'name', 'ns' );
		this.$searchForm.append( this.$namespaceInput );

		this.$searchBoxWrapper = $( '<div>' ).addClass( 'bs-extendedsearch-searchbar-wrapper' );

		//Wrap search box input in another div to make it sizable when pill is added
		this.$searchBoxWrapper.attr( 'style', 'width: ' + this.$searchBox.outerWidth() + 'px;' );
		this.$searchBox.attr( 'style' , 'display: table-cell; width: 100%;' );
		this.$searchBox.wrap( this.$searchBoxWrapper );

		//Wire the events
		this.$searchBox.on( 'keydown', this.onKeyDown.bind( this ) );
		this.$searchBox.on( 'keyup', this.onKeyUp.bind( this ) );
		this.$searchBox.on( 'paste', this.onPaste.bind( this ) );
	}

	bs.extendedSearch.SearchBar.prototype.detectNamespace = function( value ) {
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
				values: bs.extendedSearch.utils.getNamespaceNames( this.namespaces, this.namespaces[newNamespace.toLowerCase()] )
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

	bs.extendedSearch.SearchBar.prototype.generateNamespacePill = function() {
		this.removeNamespacePill();

		this.$pill = $( '<span>' ).addClass( 'bs-extendedsearch-searchbar-pill' ).html( this.namespace.text );
		this.$searchBox.before( this.$pill );
		this.$searchBox.val( this.value );
	}

	bs.extendedSearch.SearchBar.prototype.removeNamespacePill = function( clearNamespace ) {
		clearNamespace = clearNamespace || false;

		if( clearNamespace ) {
			this.namespace = '';
			this.$namespaceInput.val( '' );
		}

		this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill' ).remove();
	}

	bs.extendedSearch.SearchBar.prototype.addClearButton = function() {
		if( this.$searchContainer.find( '.bs-extendedsearch-searchbar-clear' ).length > 0 ) {
			return;
		}

		var clearButton = new OO.ui.ButtonWidget( {
			indicator: 'clear',
			framed: false
		} );

		clearButton.$element.addClass( 'bs-extendedsearch-searchbar-clear' );
		clearButton.$element.on( 'click', this.onClearSearch.bind( this ) );
		clearButton.$element.insertAfter( this.$searchBox );
		this.$searchBox.addClass( 'clear-present' );
	}

	bs.extendedSearch.SearchBar.prototype.removeClearButton = function() {
		this.$searchContainer.find( '.bs-extendedsearch-searchbar-clear' ).remove();
		this.$searchBox.removeClass( 'clear-present' );
	}

	bs.extendedSearch.SearchBar.prototype.toggleClearButton = function( value ) {
		var pillPresent =
			this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill' ).length != 0;

		if( value || pillPresent ) {
			this.addClearButton();
		} else {
			this.removeClearButton();
		}
	}

	bs.extendedSearch.SearchBar.prototype.onPaste = function( e ) {
		var beforeValue = e.target.value;
		var value = e.originalEvent.clipboardData.getData( 'Text' );
		var isChanged = beforeValue !== value;

		if( this.beforeValueChanged( e ) == false ) {
			return;
		}
		if( !isChanged ) {
			return;
		}

		//paste event is fired before value is actually changed
		//in the input - give it some time to change
		setTimeout( function() {
			this.changeValue( value );
		}.bind( this ), 200 );
	}

	bs.extendedSearch.SearchBar.prototype.onKeyUp = function( e ) {
		var value = e.target.value;
		var isChanged = this.valueBefore !== value;
		if( this.beforeValueChanged( e ) == false ) {
			return;
		}

		if( this.valueBefore == '' && value == '' && e.which == 8 ) {
			//Backspacing on empty field
			this.removeNamespacePill( true );
			isChanged = true;
		}

		if( !isChanged ) {
			return;
		}

		this.changeValue( value );
	}

	bs.extendedSearch.SearchBar.prototype.onKeyDown = function( e ) {
		this.valueBefore = e.target.value;
	}

	bs.extendedSearch.SearchBar.prototype.onClearSearch = function( e ) {
		this.$searchBox.val( '' );
		this.removeNamespacePill( true );
		this.toggleClearButton( '' );
	}

	bs.extendedSearch.SearchBar.prototype.onValueChanged = function() {
		//For others to override
	}

	bs.extendedSearch.SearchBar.prototype.setValue = function( value ) {
		this.$searchBox.val( value );
		if( this.useNamespacePills ) {
			this.detectNamespace( value );
		}
		this.toggleClearButton( value );
	}

	bs.extendedSearch.SearchBar.prototype.beforeValueChanged = function( e ) {
		//Others can override this to see if the value checking should be conducted
		return true;
	}

	bs.extendedSearch.SearchBar.prototype.changeValue = function( value ) {
		if( this.useNamespacePills && value ) {
			this.detectNamespace( value );
			if( this.namespace.text ) {
				this.$namespaceInput.val( this.namespace.text );
			}
		} else {
			this.value = value;
		}

		this.toggleClearButton( value );
		//"Fire" this only when value is actually changed
		this.onValueChanged();
	}
} )( mediaWiki, jQuery, blueSpice, document );