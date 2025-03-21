( function ( $, bs ) {
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.AutocompleteTopMatch = function ( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.type = cfg.suggestion.type;
		this.autocomplete = cfg.autocomplete;
		this.imageUri = cfg.suggestion.image_uri ||
			this.autocomplete.getIconPath( this.type );
		this.titleTrim = cfg.titleTrim || null;

		this.$element = $( '<div>' );

		this.popup = cfg.popup;

		bs.extendedSearch.AutocompleteTopMatch.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );

		this.$image = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-image' )
			.attr( 'style', 'background-image: url(' + this.imageUri + ')' );
		this.$element.append( this.$image );

		this.$info = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-info' )
			.append( this.$header, this.$type );

		if ( cfg.suggestion.modified_time ) {
			bs.extendedSearch.mixin.AutocompleteModifiedTime.call( this, {
				modified_time: cfg.suggestion.modified_time // eslint-disable-line camelcase
			} );
			this.$info.append( this.$modifiedTime );
		}

		this.$element.append(
			this.$info
		);

		this.$element.on( 'click', { pageAnchor: this.$header }, this.onResultClick );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item' );
	};

	OO.inheritClass( bs.extendedSearch.AutocompleteTopMatch, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteTopMatch, bs.extendedSearch.mixin.AutocompleteHeader );
	OO.mixinClass( bs.extendedSearch.AutocompleteTopMatch, bs.extendedSearch.mixin.AutocompleteModifiedTime );

	bs.extendedSearch.AutocompleteTopMatch.prototype.onResultClick = function ( e ) {
		const anchor = e.data.pageAnchor;
		if ( $( e.target )[ 0 ] === $( anchor )[ 0 ] ) {
			// If user clicks on the actual anchor,
			// no need to do anything here
			return;
		}
		window.location = anchor.attr( 'href' );
	};

}( jQuery, blueSpice ) );
