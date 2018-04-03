( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompletePopup = function( cfg ) {
		cfg = cfg || {};

		this.suggestions = cfg.data || [];
		this.searchTerm = cfg.searchTerm || '';
		this.namespaceId = cfg.namespaceId || 0;
		this.displayLimits = cfg.displayLimits || {};
		this.mobile = cfg.mobile || false;

		this.current = -1;

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompletePopup.parent.call( this, cfg );

		bs.extendedSearch.mixin.AutocompleteResults.call( this, cfg );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup' );
		this.$element.append( this.$primaryResults );

		if( !this.mobile ) {
			this.$element.append( this.$specialResults );
		}
	}

	OO.inheritClass( bs.extendedSearch.AutocompletePopup, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompletePopup, bs.extendedSearch.mixin.AutocompleteResults );

	/**
	 * Changes currently selected item.Used in navigation with up/down arrows
	 *
	 * @param {string} direction
	 */
	bs.extendedSearch.AutocompletePopup.prototype.changeCurrent = function( direction ) {
		if( this.displayedResults.primary.length == 0 ) {
			return;
		}

		if( direction == 'up' ) {
			if( this.current == -1 ) {
				return;
			}

			if( this.current == 0 ) {
				this.current = this.displayedResults.primary.length - 1;
			} else {
				this.current--;
			}
		} else if( direction == 'down' ) {
			if( this.current == -1 ||
				this.current == this.displayedResults.primary.length - 1 ) {
				this.current = 0;
			} else {
				this.current++;
			}
		}

		this.selectCurrent();
	}

	/**
	 * Sets "selected" class on currently seleted item
	 */
	bs.extendedSearch.AutocompletePopup.prototype.selectCurrent = function() {
		this.$primaryResults.children().removeClass( 'bs-autocomplete-result-selected' );
		var item = this.$primaryResults.children()[this.current];
		$( item ).addClass( 'bs-autocomplete-result-selected' );
	}

	/**
	 * Returns uri of currently selected item (if any).
	 *
	 * @returns {string}
	 */
	bs.extendedSearch.AutocompletePopup.prototype.getCurrentUri = function() {
		if( typeof( this.displayedResults.primary[this.current] ) == 'undefined' ) {
			return null;
		}

		var item = this.displayedResults.primary[this.current];
		return item.uri;
	}

} )( mediaWiki, jQuery, blueSpice, document );