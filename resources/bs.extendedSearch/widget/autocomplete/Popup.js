( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.AutocompletePopup = function ( cfg ) {
		cfg = cfg || {};

		this.autocomplete = cfg.autocomplete;
		this.suggestions = cfg.data || [];
		this.searchTerm = cfg.searchTerm || '';
		this.namespaceId = cfg.namespaceId || 0;
		this.displayLimits = cfg.displayLimits || {};
		this.mobile = cfg.mobile || false;
		this.searchForm = cfg.searchForm || {};
		this.titleTrim = cfg.titleTrim || null;
		// This represents implicit subpage filter, not visible in the searchbar
		this.quietSubpage = cfg.quietSubpage || null;

		this.compact = cfg.compact || false;

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompletePopup.parent.call( this, cfg );

		if ( this.quietSubpage ) {
			bs.extendedSearch.mixin.QuietSubpage.call( this, this.quietSubpage );
		}

		bs.extendedSearch.mixin.AutocompleteResults.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteCreatePageLink.call( this, cfg.pageCreateInfo );
		bs.extendedSearch.mixin.FullTextSearchButton.call( this, { canFulltextSearch: !!this.searchTerm } );

		if ( this.fullTextSearchButton ) {
			this.fullTextSearchButton.on( 'click', this.onFullTextClick.bind( this ) );
		}

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup' );

		this.$primaryResults.append( this.$quietSubpage );
		this.$element.append( this.$primaryResults );

		if ( !this.mobile && !this.compact ) {
			this.$specialResults.append( this.$actions );
			this.$element.append( this.$specialResults );
		}

		if ( this.compact ) {
			this.$element.addClass( 'compact' );
			this.$element.append( this.$actions );
		}
		$( this.$element ).on( 'focusout', ( e ) => {
			const relatedTarget = e.relatedTarget;
			if ( this.$element[ 0 ].contains( relatedTarget ) ) {
				return;
			}
			this.emit( 'closePopup' );
		} );
	};

	OO.inheritClass( bs.extendedSearch.AutocompletePopup, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.AutocompleteResults );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.AutocompleteCreatePageLink );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.FullTextSearchButton );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.QuietSubpage );

	/**
	 * Changes currently selected item.Used in navigation with up/down arrows
	 *
	 * @param {string} direction
	 * @return {HTMLElement|undefined}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.changeCurrent = function ( direction ) {
		this.getGrid();
		if ( this.popupGrid[ 0 ].length === 0 && this.popupGrid[ 1 ].length === 0 ) {
			return;
		}

		this.clearSelected();

		if ( typeof this.currentColumn === 'undefined' ) {
			this.currentColumn = 0;
		}

		if ( direction === 'up' ) {
			if ( typeof this.currentIndex === 'undefined' || this.currentIndex === 0 ) {
				this.currentIndex = this.popupGrid[ this.currentColumn ].length - 1;
			} else {
				this.currentIndex--;
			}
		} else if ( direction === 'down' ) {
			if ( typeof this.currentIndex === 'undefined' ) {
				this.currentIndex = 0;
			} else if ( this.currentIndex + 1 < this.popupGrid[ this.currentColumn ].length ) {
				this.currentIndex++;
			} else {
				this.currentIndex = 0;
			}
		} else if ( direction === 'left' ) {
			this.toggleColumn();
		} else if ( direction === 'right' ) {
			this.toggleColumn();
		}

		return this.selectCurrent();
	};

	bs.extendedSearch.AutocompletePopup.prototype.getGrid = function () {
		const leftColumn = [];
		this.$primaryResults.children().each( ( k, el ) => {
			if ( !$( el ).hasClass( 'bs-extendedsearch-autocomplete-popup-primary-item' ) ) {
				return;
			}
			leftColumn.push( el );
		} );

		const rightColumn = [];
		this.$secondaryResults.children().each( ( k, el ) => {
			rightColumn.push( el );
		} );

		this.popupGrid = [ leftColumn, rightColumn ];
	};

	bs.extendedSearch.AutocompletePopup.prototype.toggleColumn = function () {
		if ( this.currentColumn === 1 ) {
			this.currentColumn = 0;
			this.announce( mw.msg( 'bs-extendedsearch-autocomplete-popup-primary-results-aria' ) );
		} else {
			this.announce( mw.msg( 'bs-extendedsearch-autocomplete-popup-secondary-results-aria' ) );
			this.currentColumn = 1;
		}

		// If we can, we move to the same level of another column, if not
		// go back to the first element
		if ( this.popupGrid[ this.currentColumn ].length <= this.currentIndex ) {
			this.currentIndex = 0;
		}
	};

	/**
	 * Sets "selected" class on currently seleted item
	 *
	 * @return {HTMLElement}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.selectCurrent = function () {
		const selectedItem = this.popupGrid[ this.currentColumn ][ this.currentIndex ];
		$( selectedItem ).addClass( 'bs-autocomplete-result-selected' );
		this.enableIgnoreButtons();
		const itemLink = $( selectedItem ).find( 'a' )[ 0 ];
		if ( !itemLink ) {
			return selectedItem;
		}
		const titleText = $( itemLink ).attr( 'data-title' );
		this.announce( titleText );
		return selectedItem;
	};

	bs.extendedSearch.AutocompletePopup.prototype.enableIgnoreButtons = function () {
		const $ignoreBtns = $( '.bs-extendedsearch-recentlyfound-ignore-button a' );
		for ( let i = 0; i < $ignoreBtns.length; i++ ) {
			$( $ignoreBtns[ i ] ).attr( 'tabindex', 0 );
		}
	};

	bs.extendedSearch.AutocompletePopup.prototype.clearSelected = function () {
		if ( typeof this.currentColumn !== 'undefined' && typeof this.currentIndex !== 'undefined' ) {
			const selected = this.popupGrid[ this.currentColumn ][ this.currentIndex ];
			$( selected ).removeClass( 'bs-autocomplete-result-selected' );
		}
	};

	/**
	 * Returns uri of currently selected item (if any).
	 *
	 * @return {string|boolean|undefined}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.getCurrentUri = function () {
		if ( typeof this.currentColumn === 'undefined' && typeof this.currentIndex === 'undefined' ) {
			return false;
		}

		const $el = $( this.popupGrid[ this.currentColumn ][ this.currentIndex ] );
		if ( $el.length > 0 ) {
			const $anchor = $el.find( 'a' );
			if ( $anchor.length > 0 ) {
				return $anchor.attr( 'href' );
			}
		}
	};

	// Fills secondary results after the popup was created and displayed,
	// as they are retrieved in async request
	bs.extendedSearch.AutocompletePopup.prototype.addSecondary = function ( data ) {
		if ( this.mobile ) {
			// Not supported in mobile view
			return;
		}

		this.fillSecondaryResults( data );

		if ( this.$secondaryResults.children().length > 0 ) {
			this.$specialResults.append( this.$secondaryResultsLabel, this.$secondaryResults );
		}
	};

	bs.extendedSearch.AutocompletePopup.prototype.onFullTextClick = function ( e ) { // eslint-disable-line no-unused-vars
		this.searchForm.submit();
	};

}( mediaWiki, jQuery, blueSpice ) );
