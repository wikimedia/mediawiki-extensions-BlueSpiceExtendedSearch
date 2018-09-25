( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteTopMatch = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.type = cfg.suggestion.type;
		this.imageUri = cfg.suggestion.image_uri ||
				bs.extendedSearch.Autocomplete.prototype.getIconPath( this.type );

		this.$element = $( '<div>' );

		this.popup = cfg.popup;

		bs.extendedSearch.AutocompleteTopMatch.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );

		this.$image = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-image' )
			.attr( 'style', "background-image: url(" + this.imageUri + ")" );
		this.$element.append( this.$image );

		this.$info = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-info' )
			.append( this.$header, this.$type );

		if( cfg.suggestion.modified_time ) {
			bs.extendedSearch.mixin.AutocompleteModifiedTime.call( this, {
				modified_time: cfg.suggestion.modified_time
			} );
			this.$info.append( this.$modifiedTime );
		}

		this.$element.append(
			this.$info
		);

		this.$element.on( 'click', { pageAnchor: this.$header }, this.onResultClick );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item' );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteTopMatch, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteTopMatch, bs.extendedSearch.mixin.AutocompleteHeader );
	OO.mixinClass( bs.extendedSearch.AutocompleteTopMatch, bs.extendedSearch.mixin.AutocompleteModifiedTime );

	bs.extendedSearch.AutocompleteTopMatch.prototype.onResultClick = function( e ) {
		var anchor = e.data.pageAnchor;
		if( $( e.target )[0] === $( anchor )[0] ) {
			// If user clicks on the actual anchor,
			// no need to do anything here
			return;
		}
		window.location = anchor.attr( 'href' );
	}

} )( mediaWiki, jQuery, blueSpice, document );