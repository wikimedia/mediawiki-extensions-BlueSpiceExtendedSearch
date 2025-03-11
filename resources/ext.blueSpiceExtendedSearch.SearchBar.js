bs.extendedSearch.SearchBar = function ( cfg ) {
	cfg = cfg || {};

	const defaultCfg = require( './searchBarConfig.json' );
	cfg = Object.assign( defaultCfg, cfg );
	this.value = '';
	this.namespace = {};
	this.masterFilter = cfg.masterFilter || null;
	this.showRecentlyFound = typeof cfg.showRecentlyFound !== 'undefined' ? cfg.showRecentlyFound : true;

	this.useNamespacePills = typeof cfg.useNamespacePills !== 'undefined' ? cfg.useNamespacePills : true;
	this.useSubpagePills = typeof cfg.useSubpagePills !== 'undefined' ? cfg.useSubpagePills : true;

	if ( bs.extendedSearch.utils.isMobile() ) {
		cfg.cntId = cfg.cntId || 'bs-extendedsearch-mobile-box';
		cfg.inputId = cfg.inputId || 'bs-extendedsearch-mobile-input';
	} else {
		cfg.cntId = cfg.cntId || 'bs-extendedsearch-box';
		cfg.inputId = cfg.inputId || 'bs-extendedsearch-input';
	}

	this.typingTimer = null;
	this.typingDoneInterval = ( typeof cfg.typingDoneInterval !== 'undefined' ) ?
		cfg.typingDoneInterval : 200;

	this.$searchContainer = $( '#' + cfg.cntId );
	this.$searchForm = this.$searchContainer.find( 'form' );
	if ( this.$searchForm.length === 0 && this.$searchContainer.length !== 0 ) {
		this.$searchForm = this.$searchContainer;
	}
	this.$searchBox = $( '#' + cfg.inputId );
	this.$searchButton = this.$searchForm.find( 'button' );

	this.$searchBoxWrapper = $( '<div>' )
		.addClass( 'bs-extendedsearch-searchbar-wrapper' )
		.attr( 'id', cfg.cntId + '-wrapper' );

	// Wrap search box input in another div to make it sizable when pill is added
	this.$searchBox.attr( 'style', 'display: table-cell;' );
	this.$searchBox.wrap( this.$searchBoxWrapper );

	// Wire the events
	this.$searchBox.on( 'keydown', this.onKeyDown.bind( this ) );
	this.$searchBox.on( 'keyup', this.onKeyUp.bind( this ) );
	this.$searchBox.on( 'paste', this.onPaste.bind( this ) );
	this.$searchBox.on( 'focus', this.onFocus.bind( this ) );

	if ( this.masterFilter ) {
		this.useNamespacePills = false;
		this.useSubpagePills = false;
		this.mainpage = this.masterFilter.title;
		this.namespace = this.masterFilter.namespace;
		this.generateMasterFilterPill();
	}
	OO.EventEmitter.call( this );
	this.isSearchCenter = cfg.isSearchCenter || false;
};

OO.initClass( bs.extendedSearch.SearchBar );
OO.mixinClass( bs.extendedSearch.SearchBar, OO.EventEmitter );

bs.extendedSearch.SearchBar.prototype.detectNamespace = function ( value ) {
	const parts = value.split( ':' );
	if ( parts.length === 1 ) {
		this.namespace = this.namespace || {};
		return value;
	}
	if ( parts.length === 2 && parts[ 1 ] === '' ) {
		this.namespace = {};
		return '';
	}

	const newNamespace = parts.shift().replace( '_', ' ' );

	if ( !this.setNamespaceFromValue( newNamespace ) ) {
		this.namespace = {};
		return value;
	} else {
		value = parts.shift();
		this.generateNamespacePill( value );
		return value;
	}
};

bs.extendedSearch.SearchBar.prototype.resetValue = function () {
	this.removeNamespacePill( true );
	this.removeSubpagePill( true );
	this.value = '';
};

bs.extendedSearch.SearchBar.prototype.detectSubpage = function ( value ) {
	if ( this.mainpage && !this.namespace ) {
		value = [ this.mainpage, value ].join( '/' );
	}
	const parts = value.split( '/' );
	if ( parts.length === 1 ) {
		this.mainpage = this.mainpage || '';
		return value;
	}
	if ( parts.length > 1 && parts[ parts.length - 1 ] === '' ) {
		return '';
	}

	value = parts.pop();
	this.mainpage = parts.join( '/' );
	this.generateSubpagePill( value );
	return value;
};

bs.extendedSearch.SearchBar.prototype.setNamespaceFromValue = function ( nsText ) {
	if ( nsText === '' ) {
		// Explicitly main
		this.namespace = {
			id: 0,
			text: mw.message( 'bs-ns_main' ).plain()
		};
		return true;
	}

	const namespaceId = this.findNamespace( bs.extendedSearch.utils.normalizeNamespaceName( nsText ) );
	if ( namespaceId !== null ) {
		this.namespace = {
			id: namespaceId,
			text: nsText,
			values: bs.extendedSearch.utils.getNamespaceNames( this.namespaces, namespaceId )
		};
		return true;
	}

	// NS cannot be set
	return false;
};

bs.extendedSearch.SearchBar.prototype.findNamespace = function ( nsText ) {
	if ( !this.namespaces ) {
		this.namespaces = bs.extendedSearch.utils.getNamespacesList();
	}
	nsText = bs.extendedSearch.utils.normalizeNamespaceName( nsText );
	for ( const nsName in this.namespaces ) {
		if ( !this.namespaces.hasOwnProperty( nsName ) ) {
			continue;
		}
		if ( nsName.toLowerCase() === nsText ) {
			return this.namespaces[ nsName ];
		}
	}

	return null;
};

bs.extendedSearch.SearchBar.prototype.generateNamespacePill = function ( value ) {
	value = value || this.value;
	this.removeNamespacePill();
	const sbW = this.$searchBox.outerWidth();

	this.$pill = $( '<span>' )
		.addClass( 'bs-extendedsearch-searchbar-pill namespace-pill' )
		.html( this.namespace.text + ':' );
	this.$searchBox.before( this.$pill );
	this.setSearchBoxWidthInline( sbW - this.$pill.outerWidth() );

	this.$searchBox.val( value );
};

bs.extendedSearch.SearchBar.prototype.generateSubpagePill = function ( value ) {
	value = value || this.value;
	this.removeSubpagePill();

	this.$pill = $( '<span>' )
		.addClass( 'bs-extendedsearch-searchbar-pill subpage-pill' );

	const mainpageBits = this.mainpage.split( '/' );
	if ( mainpageBits.length > 1 && this.mainpage.length > 30 ) {
		this.$pill.attr( 'title', this.mainpage );
		this.$pill.html( '.../' + mainpageBits.pop() + '/' );
	} else {
		this.$pill.html( this.mainpage + '/' );
	}

	this.$searchBox.before( this.$pill );
	const sbW = this.$searchBox.outerWidth();
	this.setSearchBoxWidthInline( sbW - this.$pill.outerWidth() );
	this.$searchBox.val( value );
};

bs.extendedSearch.SearchBar.prototype.generateMasterFilterPill = function () {
	this.removeMasterFilterPill();

	this.$pill = $( '<span>' )
		.addClass( 'bs-extendedsearch-searchbar-pill subpage-pill master-filter-pill' );

	this.$pill.html( new OO.ui.IconWidget( {
		icon: 'funnel', flags: [ 'progressive', 'primary' ]
	} ).$element );
	this.$pill.attr( 'title', mw.message(
		'bs-extendedsearch-searchbar-master-filter-tooltip', this.getMasterFilterPage()
	).text() );

	this.$searchBox.before( this.$pill );
	const sbW = this.$searchBox.outerWidth();
	this.setSearchBoxWidthInline( sbW - this.$pill.outerWidth() );
};

bs.extendedSearch.SearchBar.prototype.removeMasterFilterPill = function () {
	const pill = this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill.master-filter-pill' );
	if ( pill.length === 0 ) {
		return false;
	}
	this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + pill.outerWidth() );
	pill.remove();
	return true;
};

bs.extendedSearch.SearchBar.prototype.removeNamespacePill = function ( clearNamespace ) {
	clearNamespace = clearNamespace || false;

	if ( clearNamespace ) {
		this.namespace = {};
	}

	const pill = this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill.namespace-pill' );
	if ( pill.length === 0 ) {
		return false;
	}
	this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + pill.outerWidth() );
	pill.remove();
	return true;
};

bs.extendedSearch.SearchBar.prototype.removeSubpagePill = function ( clearMainpage ) {
	clearMainpage = clearMainpage || false;

	if ( clearMainpage ) {
		this.mainpage = '';
	}

	const pill = this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill.subpage-pill' );
	if ( pill.length === 0 ) {
		return false;
	}
	this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + pill.outerWidth() );
	pill.remove();
	return true;
};

bs.extendedSearch.SearchBar.prototype.addClearButton = function () {
	if ( this.$searchContainer.find( '.bs-extendedsearch-searchbar-clear' ).length > 0 ) {
		return;
	}

	const clearButton = new OO.ui.ButtonWidget( {
		indicator: 'clear',
		framed: false
	} );
	clearButton.$button.attr( 'aria-label', mw.msg( 'bs-extendedsearch-close-search-button-aria-label' ) );
	clearButton.$button.on( 'click', this.onClearSearch.bind( this ) );
	clearButton.$button.on( 'keydown', this.onClearSearchKeyDown.bind( this ) );

	const sbW = this.$searchBox.outerWidth();

	clearButton.$element.addClass( 'bs-extendedsearch-searchbar-clear' );
	clearButton.$element.insertAfter( this.$searchBox );
	const cbW = clearButton.$element.outerWidth();

	this.setSearchBoxWidthInline( sbW - cbW );
	this.$searchContainer.addClass( 'clear-present' );

	if ( this.isSearchCenter ) {
		// Manually make sure that if this button is in focus, next target for tab starts in `.bs-es-tools` container
		clearButton.$element.on( 'keydown', ( e ) => {
			const $tools = $( '.bs-es-tools' );
			if ( $tools.length === 0 ) {
				return;
			}
			if ( e.which === 9 && !e.shiftKey ) {
				e.preventDefault();
				// First first focusable element in ( '.bs-es-tools' )
				$tools.find( 'button, a, input' ).first().focus(); // eslint-disable-line no-jquery/no-event-shorthand
			}
		} );
	}
};

bs.extendedSearch.SearchBar.prototype.removeClearButton = function () {
	const $clearButton = this.$searchContainer.find( '.bs-extendedsearch-searchbar-clear' );
	if ( $clearButton.length === 0 ) {
		return;
	}
	this.setSearchBoxWidthInline( this.$searchBox.outerWidth() + $clearButton.outerWidth() );
	$clearButton.remove();
	this.$searchContainer.removeClass( 'clear-present' );
};

bs.extendedSearch.SearchBar.prototype.setSearchBoxWidthInline = function () {
	const value = 'display: table-cell;';

	this.$searchBox.attr( 'style', value );
};

bs.extendedSearch.SearchBar.prototype.toggleClearButton = function ( value ) {
	let pillPresent =
		this.$searchContainer.find( '.bs-extendedsearch-searchbar-pill' ).length !== 0;

	if ( !this.useNamespacePills ) {
		pillPresent = false;
	}

	if ( value || pillPresent ) {
		this.addClearButton();
	} else {
		this.removeClearButton();
	}
};

bs.extendedSearch.SearchBar.prototype.onPaste = function ( e ) {
	const beforeValue = e.target.value;
	const value = e.originalEvent.clipboardData.getData( 'Text' );
	const isChanged = beforeValue !== value;
	const shouldAbort = { abort: false };
	this.emit( 'beforeValueChanged', e, shouldAbort );
	if ( shouldAbort.abort === true ) {
		return;
	}
	if ( !isChanged ) {
		return;
	}

	// paste event is fired before value is actually changed
	// in the input - give it some time to change
	setTimeout( () => {
		this.changeValue( value );
	}, 200 );
};

bs.extendedSearch.SearchBar.prototype.onKeyUp = function ( e ) {
	const value = e.target.value;
	let isChanged = ( this.valueBefore || '' ) !== value;
	const shouldAbort = { abort: false };
	this.emit( 'beforeValueChanged', e, shouldAbort );
	if ( shouldAbort.abort === true ) {
		return;
	}
	if ( this.valueBefore === '' && value === '' && e.which === 8 ) {
		// Backspacing on empty field
		if ( this.useSubpagePills ) {
			if ( this.removeSubpagePill( true ) ) {
				isChanged = true;
			}
		}
		if ( this.useNamespacePills && isChanged === false ) {
			if ( this.removeNamespacePill( true ) ) {
				isChanged = true;
			}
		}
	}

	if ( !isChanged ) {
		return;
	}

	// Fire value change only after user has finished
	// typing - to avoid sending requests mid-typing
	clearTimeout( this.typingTimer );
	this.typingTimer = setTimeout( () => {
		this.changeValue( value );
	}, this.typingDoneInterval );
};

bs.extendedSearch.SearchBar.prototype.onKeyDown = function ( e ) {
	this.valueBefore = e.target.value;
};

bs.extendedSearch.SearchBar.prototype.onClearSearch = function ( e ) {
	this.emit( 'beforeClearSearch', e );

	this.$searchBox.val( '' );
	if ( this.useNamespacePills ) {
		this.removeNamespacePill( true );
	}
	if ( this.useSubpagePills ) {
		this.removeSubpagePill( true );
	}
	this.toggleClearButton( '' );

	this.emit( 'clearSearch', e );
	this.$searchBox.focus(); // eslint-disable-line no-jquery/no-event-shorthand
};

bs.extendedSearch.SearchBar.prototype.onClearSearchKeyDown = function ( e ) {
	if ( e.which === 13 ) {
		this.onClearSearch();
	}
};

bs.extendedSearch.SearchBar.prototype.setValue = function ( value ) {
	this.$searchBox.val( value );
	if ( this.useNamespacePills ) {
		value = this.detectNamespace( value );
	}
	if ( this.useSubpagePills ) {
		value = this.detectSubpage( value );
	}

	this.value = value;
	this.toggleClearButton( value );
};

bs.extendedSearch.SearchBar.prototype.changeValue = function ( value ) {
	if ( this.useNamespacePills && value ) {
		value = this.detectNamespace( value );
	}
	if ( this.useSubpagePills && value ) {
		value = this.detectSubpage( value );
	}

	this.value = value;

	this.toggleClearButton( value );
	// Emit only when value is actually changed
	this.emit( 'valueChanged' );
};

bs.extendedSearch.SearchBar.prototype.isMasterFilterActive = function () {
	return this.quietSubpageSupressed;
};

bs.extendedSearch.SearchBar.prototype.getMasterFilterPage = function () {
	if ( this.masterFilter && !this.isMasterFilterActive() ) {
		if ( this.namespace.id !== 0 ) {
			return this.namespace.text + ':' + this.mainpage;
		} else {
			return this.mainpage;
		}
	}

	return null;
};

bs.extendedSearch.SearchBar.prototype.suppressQuietSubpage = function ( value ) {
	if ( !this.masterFilter ) {
		return;
	}
	if ( value === 'suppress' ) {
		this.quietSubpageSupressed = true;
		this.mainpage = '';
		this.namespace = {};
		this.removeMasterFilterPill();
	} else if ( value === 'arm' && this.quietSubpageSupressed ) {
		this.quietSubpageSupressed = false;
		this.quietSubpageArmToRestore = true;
	} else if ( value === 'restore' && this.quietSubpageArmToRestore ) {
		this.generateMasterFilterPill();
		this.quietSubpageArmToRestore = false;
		this.mainpage = this.masterFilter.title;
		this.namespace = this.masterFilter.namespace;
	}
};

bs.extendedSearch.SearchBar.prototype.onFocus = function () {
	if ( this.$searchBox.val() ) {
		return;
	}

	this.emit( 'emptyFocus' );
};

bs.extendedSearch.SearchBar.prototype.setPending = function () {
	this.$searchBox.addClass( 'oo-ui-pendingElement-pending' );
};

bs.extendedSearch.SearchBar.prototype.clearPending = function () {
	this.$searchBox.removeClass( 'oo-ui-pendingElement-pending' );
};
