( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.SearchBar = function( cfg ) {
		this.init( cfg );
	};

	bs.extendedSearch.SearchBar.prototype.init = function( cfg ) {
		cfg = cfg || {};

		this.mobile = cfg.mobile || false;
		this.value = '';
		this.namespace = {};

		this.useNamespacePills = true;
		if( cfg.useNamespacePills === false ) {
			this.useNamespacePills = false;
		}
		this.useSubpagePills = true;
		if( cfg.useSubpagePills === false ) {
			this.useSubpagePills = false;
		}

		if( bs.extendedSearch.utils.isMobile() ) {
			this.mobile = true;
			cfg.cntId = cfg.cntId || 'bs-extendedsearch-mobile-box';
			cfg.inputId = cfg.inputId || 'bs-extendedsearch-mobile-input';
		} else {
			cfg.cntId = cfg.cntId || 'bs-extendedsearch-box';
			cfg.inputId = cfg.inputId || 'bs-extendedsearch-input';
		}

		this.typingTimer = null;
		this.typingDoneInterval = cfg.typingDoneInterval || 500;

		this.$searchContainer = $( '#' + cfg.cntId );
		this.$searchForm = this.$searchContainer.find( 'form' );
		this.$searchBox = $( '#' + cfg.inputId );
		this.$searchButton = this.$searchForm.find( 'button' );

		this.$searchBoxWrapper = $( '<div>' )
			.addClass( 'bs-extendedsearch-searchbar-wrapper' )
			.attr( 'id', cfg.cntId + '-wrapper' );

		//Wrap search box input in another div to make it sizable when pill is added
		this.$searchBoxWrapper.attr( 'style', 'width: ' + this.$searchBox.outerWidth() + 'px; !important' );
		this.$searchBox.attr( 'style' , 'display: table-cell;' );
		this.$searchBox.wrap( this.$searchBoxWrapper );

		//Wire the events
		this.$searchBox.on( 'keydown', this.onKeyDown.bind( this ) );
		this.$searchBox.on( 'keyup', this.onKeyUp.bind( this ) );
		this.$searchBox.on( 'paste', this.onPaste.bind( this ) );
	};

	bs.extendedSearch.SearchBar.prototype.detectNamespace = function( value ) {
		var parts = value.split( ':' );
		if( parts.length === 1 ) {
			this.namespace = this.namespace || {};
			return value;
		}
		if( parts.length === 2 && parts[1] === '' ) {
			this.namespace = {};
			return '';
		}

		var newNamespace = parts.shift();

		if( !this.setNamespaceFromValue( newNamespace ) ) {
			this.namespace = {};
			return value;
		} else {
			value = parts.shift();
			this.generateNamespacePill( value );
			return value;
		}
	};

	bs.extendedSearch.SearchBar.prototype.detectSubpage = function( value ) {
		var parts = value.split( '/' );
		if( parts.length === 1 ) {
			this.mainpage = this.mainpage || '';
			return value;
		}
		if( parts.length === 2 && parts[1] === '' ) {
			this.mainpage = '';
			return '';
		}

		this.mainpage = parts.shift();
		value = parts.shift();
		this.generateSubpagePill( value );
		return value;
	};

	bs.extendedSearch.SearchBar.prototype.setNamespaceFromValue = function( nsText ) {
		if( !this.namespaces ) {
			this.namespaces = bs.extendedSearch.utils.getNamespacesList();
		}

		if( nsText === '' ) {
			// Explicitly main
			this.namespace = {
				id: 0,
				text: mw.message( 'bs-ns_main' ).plain()
			};
			return true;
		}

		if( nsText.toLowerCase() in this.namespaces ) {
			newNamespace = {
				id: this.namespaces[nsText.toLowerCase()],
				text: nsText,
				values: bs.extendedSearch.utils.getNamespaceNames( this.namespaces, this.namespaces[nsText.toLowerCase()] )
			};

			if( newNamespace.id !== this.namespace.id ) {
				this.namespace = newNamespace;
			}
			return true;
		}

		//NS cannot be set
		return false;
	};

	bs.extendedSearch.SearchBar.prototype.generateNamespacePill = function( value ) {
		value = value || this.value;
		this.removeNamespacePill();
		var sbW = this.$searchBox.outerWidth();

		this.$pill = $( '<span>' )
			.addClass( 'bs-extendedsearch-searchbar-pill namespace-pill' )
			.html( this.namespace.text + ':' );
		this.$searchBox.before( this.$pill );
		this.setSearchBoxWidthInline( sbW - this.$pill.outerWidth(), true );
		this.$searchBox.val( value );
	};

	bs.extendedSearch.SearchBar.prototype.generateSubpagePill = function( value ) {
		value = value || this.value;
		this.removeSubpagePill();

		var sbW = this.$searchBox.outerWidth();

		this.$pill = $( '<span>' )
			.addClass( 'bs-extendedsearch-searchbar-pill subpage-pill' )
			.html( this.mainpage + '/' );
		this.$searchBox.before( this.$pill );
		this.setSearchBoxWidthInline( sbW - this.$pill.outerWidth(), true );
		this.$searchBox.val( value );
	}

	bs.extendedSearch.SearchBar.prototype.removeNamespacePill = function( clearNamespace ) {
		clearNamespace = clearNamespace || false;

		if( clearNamespace ) {
			this.namespace = {};
		}

		var pill = this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill.namespace-pill' );
		if( pill.length === 0 ) {
			return false;
		}
		this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + pill.outerWidth(), true );
		pill.remove();
		return true;
	};

	bs.extendedSearch.SearchBar.prototype.removeSubpagePill = function( clearMainpage ) {
		clearMainpage = clearMainpage || false;

		if( clearMainpage ) {
			this.mainpage = '';
		}

		var pill = this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill.subpage-pill' );
		if( pill.length === 0 ) {
			return false;
		}
		this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + pill.outerWidth(), true );
		pill.remove();
		return true;
	};

	bs.extendedSearch.SearchBar.prototype.addClearButton = function() {
		if( this.$searchContainer.find( '.bs-extendedsearch-searchbar-clear' ).length > 0 ) {
			return;
		}

		var clearButton = new OO.ui.ButtonWidget( {
			indicator: 'clear',
			framed: false
		} );

		var sbW = this.$searchBox.outerWidth();

		clearButton.$element.addClass( 'bs-extendedsearch-searchbar-clear' );
		clearButton.$element.insertAfter( this.$searchBox );
		clearButton.$element.on( 'click', this.onClearSearch.bind( this ) );
		var cbW = clearButton.$element.outerWidth();

		this.setSearchBoxWidthInline( sbW - cbW, true );
		this.$searchBox.addClass( 'clear-present' );
	};

	bs.extendedSearch.SearchBar.prototype.removeClearButton = function() {
		var $clearButton = this.$searchContainer.find( '.bs-extendedsearch-searchbar-clear' );
		if( $clearButton.length === 0 ){
			return;
		}
		this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + $clearButton.outerWidth(), true );
		$clearButton.remove();
		this.$searchBox.removeClass( 'clear-present' );
	};

	bs.extendedSearch.SearchBar.prototype.setSearchBoxWidthInline = function( width, important ) {
		important = important || false;
		var value = 'display: table-cell; width:' + width + 'px';
		if( important ) {
			value += " !important ";
		}

		this.$searchBox.attr( 'style', value );
	};

	bs.extendedSearch.SearchBar.prototype.toggleClearButton = function( value ) {
		var pillPresent =
			this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill' ).length != 0;

		if( !this.useNamespacePills ) {
			pillPresent = false;
		}

		if( value || pillPresent ) {
			this.addClearButton();
		} else {
			this.removeClearButton();
		}
	};

	bs.extendedSearch.SearchBar.prototype.onPaste = function( e ) {
		var beforeValue = e.target.value;
		var value = e.originalEvent.clipboardData.getData( 'Text' );
		var isChanged = beforeValue !== value;

		if( this.beforeValueChanged( e ) === false ) {
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
	};

	bs.extendedSearch.SearchBar.prototype.onKeyUp = function( e ) {
		var value = e.target.value;
		var isChanged = this.valueBefore !== value;
		if( this.beforeValueChanged( e ) === false ) {
			return;
		}

		if( this.valueBefore === '' && value === '' && e.which === 8 ) {
			//Backspacing on empty field
			if( this.useSubpagePills ) {
				if( this.removeSubpagePill( true ) ) {
					isChanged = true;
				}
			}
			if( this.useNamespacePills && isChanged === false ) {
				if( this.removeNamespacePill( true ) ) {
					isChanged = true;
				}
			}
		}

		if( !isChanged ) {
			return;
		}

		//Fire value change only after user has finished
		//typing - to avoid sending requests mid-typing
		clearTimeout( this.typingTimer );
		this.typingTimer = setTimeout( function() {
			this.changeValue( value );
		}.bind( this ), this.typingDoneInterval );
	};

	bs.extendedSearch.SearchBar.prototype.onKeyDown = function( e ) {
		this.valueBefore = e.target.value;
	};

	bs.extendedSearch.SearchBar.prototype.onClearSearch = function( e ) {
		this.$searchBox.val( '' );
		if( this.useNamespacePills ) {
			this.removeNamespacePill( true );
		}
		if( this.useSubpagePills ) {
			this.removeSubpagePill( true );
		}
		this.toggleClearButton( '' );
	};

	bs.extendedSearch.SearchBar.prototype.onValueChanged = function() {
		//For others to override
	};

	bs.extendedSearch.SearchBar.prototype.setValue = function( value ) {
		this.$searchBox.val( value );
		if( this.useNamespacePills ) {
			value = this.detectNamespace( value );
		}
		if( this.useSubpagePills ) {
			value = this.detectSubpage( value );
		}

		this.value = value;
		this.toggleClearButton( value );
	};

	bs.extendedSearch.SearchBar.prototype.beforeValueChanged = function( e ) {
		//Others can override this to see if the value checking should be conducted
		return true;
	};

	bs.extendedSearch.SearchBar.prototype.changeValue = function( value ) {
		if( this.useNamespacePills && value ) {
			value = this.detectNamespace( value );
		} if( this.useSubpagePills && value ) {
			value = this.detectSubpage( value );
		}

		this.value = value;

		this.toggleClearButton( value );
		//"Fire" this only when value is actually changed
		this.onValueChanged();
	};
} )( mediaWiki, jQuery, blueSpice, document );
