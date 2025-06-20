( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.AutocompletePopup = function ( cfg ) {
		cfg = cfg || {};

		this.autocomplete = cfg.autocomplete;
		this.primaryResults = cfg.data || [];
		this.searchTerm = cfg.searchTerm || '';
		this.namespaceId = cfg.namespaceId || 0;
		this.displayLimits = cfg.displayLimits || {};
		this.searchForm = cfg.searchForm || {};
		this.titleTrim = cfg.titleTrim || null;
		// This represents implicit subpage filter, not visible in the searchbar
		this.quietSubpage = cfg.quietSubpage || null;

		this.displayedResults = {
			primary: [],
			secondary: []
		};

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompletePopup.parent.call( this, cfg );

		if ( this.quietSubpage ) {
			bs.extendedSearch.mixin.QuietSubpage.call( this, this.quietSubpage );
		}
		this.headerText = cfg.headerText || mw.msg( 'bs-extendedsearch-autocomplete-result-primary-results-label' );

		this.$primaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-primary' );

		this.$secondaryResultsLabel = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-special-item-label' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-secondary-results-header-label' ).plain() );
		this.$secondaryResults = $( '<div>' ).addClass( 'bs-extendedsearch-autocomplete-popup-secondary' );
		this.$secondaryResults.append( this.$secondaryResultsLabel );
		this.$secondaryResults.hide();

		this.$announcer = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-announcer visually-hidden' )
			.attr( 'aria-live', 'polite' );
		this.$element.append( this.$announcer );

		if ( this.headerText ) {
			this.$primaryResults.append(
				new OO.ui.LabelWidget( { label: this.headerText } ).$element
			);
		}
		bs.extendedSearch.mixin.ContextOptions.call( this, cfg );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup' );

		this.$primaryResults.append( this.$quietSubpage );
		this.$element.append( this.$contextOptions, this.$primaryResults, this.$secondaryResults );

		$( this.$element ).on( 'focusout', ( e ) => {
			const relatedTarget = e.relatedTarget;
			if ( this.$element[ 0 ].contains( relatedTarget ) ) {
				return;
			}
			this.emit( 'closePopup' );
		} );

		this.renderPrimaryResults();
	};

	OO.inheritClass( bs.extendedSearch.AutocompletePopup, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.ContextOptions );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.QuietSubpage );

	bs.extendedSearch.AutocompletePopup.prototype.getDisplayedResults = function () {
		return this.displayedResults;
	};

	bs.extendedSearch.AutocompletePopup.prototype.renderPrimaryResults = function () {
		const limit = this.displayLimits.primary;
		const resultsToRender = this.primaryResults.slice( 0, limit );
		for ( let i = 0; i < resultsToRender.length; i++ ) {
			const suggestion = resultsToRender[ i ];
			const pageItem = this.getResultWidget( suggestion );
			pageItem.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item' );
			this.$primaryResults.append( pageItem.$element );
		}
		this.displayedResults.primary.push( ...resultsToRender );

		if ( this.displayedResults.primary.length === 0 ) {
			this.announce( mw.msg( 'bs-extendedsearch-autocomplete-result-primary-no-results-label' ) );
			this.$primaryResults.append(
				$( '<div>' )
					.addClass( 'bs-extendedsearch-autocomplete-popup-no-results' )
					.html( mw.message( 'bs-extendedsearch-autocomplete-result-primary-no-results-label' ).plain() )
			);
		} else {
			const cnt = this.displayedResults.primary.length;
			this.announce( mw.msg( 'bs-extendedsearch-autocomplete-header-aria', cnt ) );

		}
	};

	bs.extendedSearch.AutocompletePopup.prototype.renderSecondaryResults = function ( suggestions ) {
		const limit = this.displayLimits.secondary;
		const resultsToRender = suggestions.slice( 0, limit );

		for ( let i = 0; i < resultsToRender.length; i++ ) {
			const suggestion = resultsToRender[ i ];
			const pageItem = this.getResultWidget( suggestion );
			pageItem.$element.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-item' );
			this.$secondaryResults.append( pageItem.$element );
		}
		this.displayedResults.secondary.push( ...resultsToRender );
	};

	bs.extendedSearch.AutocompletePopup.prototype.getResultWidget = function ( suggestion ) {
		return new bs.extendedSearch.AutocompleteResult( {
			suggestion: suggestion,
			term: this.searchTerm,
			popup: this,
			titleTrim: this.titleTrim
		} );
	};

	bs.extendedSearch.AutocompletePopup.prototype.announce = function ( ariaLabel ) {
		this.$announcer.text( ariaLabel );
	};

	/**
	 * Changes currently selected item.Used in navigation with up/down arrows
	 *
	 * @param {string} direction
	 * @return {HTMLElement|undefined}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.changeCurrent = function ( direction ) {
		this.setIterableItems();
		if ( this.iterableItems.length === 0 ) {
			return;
		}
		this.clearSelected();

		if ( direction === 'up' ) {
			if ( typeof this.currentIndex === 'undefined' || this.currentIndex === 0 ) {
				this.currentIndex = this.iterableItems.length - 1;
			} else {
				this.currentIndex--;
			}
		} else if ( direction === 'down' ) {
			if ( typeof this.currentIndex === 'undefined' ) {
				this.currentIndex = 0;
			} else if ( this.currentIndex + 1 < this.iterableItems.length ) {
				this.currentIndex++;
			} else {
				this.currentIndex = 0;
			}
		}

		return this.selectCurrent();
	};

	bs.extendedSearch.AutocompletePopup.prototype.setIterableItems = function () {
		this.iterableItems = [];
		this.$contextOptions.children( '.bs-extendedsearch-autocomplete-popup-context-option' )
			.each( ( k, el ) => {
				this.iterableItems.push( el );
			} );
		this.$primaryResults.children( '.bs-extendedsearch-autocomplete-popup-primary-item' )
			.each( ( k, el ) => {
				this.iterableItems.push( el );
			} );

		this.$secondaryResults.children( '.bs-extendedsearch-autocomplete-popup-secondary-item' )
			.each( ( k, el ) => {
				this.iterableItems.push( el );
			} );
	};

	/**
	 * Sets "selected" class on currently selected item
	 *
	 * @return {HTMLElement}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.selectCurrent = function () {
		const selectedItem = this.iterableItems[ this.currentIndex ];
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
		if ( typeof this.currentIndex !== 'undefined' ) {
			const selected = this.iterableItems[ this.currentIndex ];
			$( selected ).removeClass( 'bs-autocomplete-result-selected' );
		}
	};

	// Fills secondary results after the popup was created and displayed,
	// as they are retrieved in async request
	bs.extendedSearch.AutocompletePopup.prototype.addSecondary = function ( data ) {
		this.renderSecondaryResults( data );

		if ( this.displayedResults.secondary.length > 0 ) {
			this.$secondaryResults.show();
		}
		this.setIterableItems();
	};

	bs.extendedSearch.AutocompletePopup.prototype.navigateToSelectedItem = function () {
		if ( !this.iterableItems ) {
			return false;
		}
		const $el = $( this.iterableItems[ this.currentIndex ] );
		if ( $el.length === 0 ) {
			return false;
		}
		if ( $el.hasClass( 'bs-extendedsearch-autocomplete-popup-context-option' ) ) {
			this.setLookupContextFromContextId( $el.attr( 'id' ) );
			return false;
		}
		const $anchor = $el.find( 'a' );
		if ( $anchor.length > 0 && $anchor.attr( 'href' ) ) {
			window.location.href = $anchor.attr( 'href' );
			return true;
		}

		return false;
	};

}( mediaWiki, jQuery, blueSpice ) );
