( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompletePopup = function( cfg ) {
		cfg = cfg || {};

		this.suggestions = cfg.data || [];
		this.searchTerm = cfg.searchTerm || '';
		this.namespaceId = cfg.namespaceId || 0;
		this.displayLimits = cfg.displayLimits || {};
		this.mobile = cfg.mobile || false;
		this.searchForm = cfg.searchForm || {};

		this.compact = cfg.compact || false;

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompletePopup.parent.call( this, cfg );

		bs.extendedSearch.mixin.AutocompleteResults.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteCreatePageLink.call( this, cfg.pageCreateInfo );
		bs.extendedSearch.mixin.FullTextSearchButton.call( this, {} );

		this.fullTextSearchButton.on( 'click', this.onFullTextClick.bind( this ) );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup' );

		if( this.compact ) {
			this.$element.addClass( 'compact' );
		}
		this.$element.append( this.$primaryResults );

		if( !this.mobile && !this.compact ) {
			this.$element.append( this.$specialResults );
		}
	}

	OO.inheritClass( bs.extendedSearch.AutocompletePopup, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.AutocompleteResults );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.AutocompleteCreatePageLink );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.FullTextSearchButton );

	/**
	 * Changes currently selected item.Used in navigation with up/down arrows
	 *
	 * @param {string} direction
	 */
	bs.extendedSearch.AutocompletePopup.prototype.changeCurrent = function( direction ) {
		this.getGrid();
		if( this.popupGrid[0].length === 0 && this.popupGrid[1].length === 0 ) {
			return;
		}

		this.clearSelected();

		if( typeof this.currentColumn === 'undefined' ) {
			this.currentColumn = 0;
		}

		if( direction == 'up' ) {
			if( typeof this.currentIndex === 'undefined' || this.currentIndex === 0 ) {
				this.currentIndex = this.popupGrid[this.currentColumn].length - 1;
			} else {
				this.currentIndex--;
			}
		} else if( direction === 'down' ) {
			if( typeof this.currentIndex === 'undefined' ) {
				this.currentIndex = 0;
			} else if( this.currentIndex + 1 < this.popupGrid[this.currentColumn].length ) {
				this.currentIndex ++;
			} else {
				this.currentIndex = 0;
			}
		} else if( direction === 'left' ) {
			this.toggleColumn();
		} else if( direction === 'right' ) {
			this.toggleColumn();
		}

		this.selectCurrent();
	}

	bs.extendedSearch.AutocompletePopup.prototype.getGrid = function() {
		var leftColumn = [];
		this.$primaryResults.children().each( function( k, el ) {
			leftColumn.push( el );
		} );

		var rightColumn = [];
		if( this.$createPageLink ) {
			rightColumn.push( this.$createPageLink );
		}
		rightColumn.push( this.fullTextSearchButton.$element );

		this.$secondaryResults.children().each( function( k, el ) {
			rightColumn.push( el );
		} );

		this.popupGrid = [ leftColumn, rightColumn ];
	}

	bs.extendedSearch.AutocompletePopup.prototype.toggleColumn = function() {
		if( this.currentColumn === 1 ) {
			this.currentColumn = 0;
		} else {
			this.currentColumn = 1;
		}

		// If we can, we move to the same level of another column, if not
		// go back to the first element
		if( this.popupGrid[this.currentColumn].length <= this.currentIndex ) {
			this.currentIndex = 0;
		}
	}

	/**
	 * Sets "selected" class on currently seleted item
	 */
	bs.extendedSearch.AutocompletePopup.prototype.selectCurrent = function() {
		var selectedItem = this.popupGrid[this.currentColumn][this.currentIndex];
		$( selectedItem ).addClass( 'bs-autocomplete-result-selected' );
		return;
	}

	bs.extendedSearch.AutocompletePopup.prototype.clearSelected = function() {
		if( typeof this.currentColumn !== 'undefined' && typeof this.currentIndex !== 'undefined' ) {
			var selected = this.popupGrid[this.currentColumn][this.currentIndex];
			$( selected ).removeClass( 'bs-autocomplete-result-selected' );
		}
	}

	/**
	 * Returns uri of currently selected item (if any).
	 *
	 * @returns {string}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.getCurrentUri = function() {
		if( typeof this.currentColumn === 'undefined' && typeof this.currentIndex === 'undefined' ) {
			return false;
		}

		var $el = $( this.popupGrid[this.currentColumn][this.currentIndex] );
		if( $el.length > 0 ) {
			var $anchor = $el.find( 'a' );
			if( $anchor.length > 0 ) {
				return $anchor.attr( 'href' );
			}
		}
	}

	//Fills secondary results after the popup was created and displayed,
	//as they are retrieved in async request
	bs.extendedSearch.AutocompletePopup.prototype.addSecondary = function( data ) {
		if( this.mobile ) {
			//Not supported in mobile view
			return;
		}

		this.fillSecondaryResults( data );

		if( this.$secondaryResults.children().length > 0 ) {
			this.$specialResults.append( this.$secondaryResultsLabel, this.$secondaryResults );
		}
	}

	bs.extendedSearch.AutocompletePopup.prototype.onFullTextClick = function( e ) {
		this.searchForm.submit();
	}

} )( mediaWiki, jQuery, blueSpice, document );
