( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteTopMatch = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.type = cfg.suggestion.type;
		this.imageUri = cfg.suggestion.image_uri ||
				bs.extendedSearch.Autocomplete.getIconPath( this.type );

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteTopMatch.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );

		var $image = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-image' )
			.attr( 'style', "background-image: url(" + this.imageUri + ")" );
		this.$element.append( $image );

		this.$element.append(
			this.$header
		);

		/*this.$element.append( $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-header' )
			.html( this.basename )
		);*/

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item' );
		/*this.$element.on( 'mouseenter', this.onMouseEnter.bind( this ) );
		this.$element.on( 'mouseleave', this.onMouseLeave.bind( this ) );*/
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteTopMatch, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteTopMatch, bs.extendedSearch.mixin.AutocompleteHeader );

} )( mediaWiki, jQuery, blueSpice, document );