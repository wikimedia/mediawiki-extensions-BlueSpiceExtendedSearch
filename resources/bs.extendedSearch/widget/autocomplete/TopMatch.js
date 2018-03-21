( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteTopMatch = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.uri = cfg.suggestion.uri;
		this.type = cfg.suggestion.type;
		this.editLink = cfg.suggestion.edit_uri || null;
		this.imageUri = cfg.suggestion.image_uri ||
				bs.extendedSearch.Autocomplete.getIconPath( this.type );

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteTopMatch.parent.call( this, cfg );

		var $image = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-image' )
			.attr( 'style', "background-image: url(" + this.imageUri + ")" );
		this.$element.append( $image );

		this.$element.append(
			$( '<a>' )
				.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-visit' )
				.attr( 'href', this.uri )
				.html( mw.message( 'bs-extendedsearch-autocomplete-result-visit-label' ).plain() )
		);

		if( this.editLink ) {
			this.$element.append(
				$( '<a>' )
					.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-edit' )
					.attr( 'href', this.editLink )
					.html( mw.message( 'bs-extendedsearch-autocomplete-result-edit-label' ).plain() )
			);
		}

		this.$element.append( $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item-header' )
			.html( this.basename )
		);

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-top-match-item' );
		this.$element.on( 'mouseenter', this.onMouseEnter.bind( this ) );
		this.$element.on( 'mouseleave', this.onMouseLeave.bind( this ) );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteTopMatch, OO.ui.Widget );

	bs.extendedSearch.AutocompleteTopMatch.prototype.onMouseEnter = function( e ) {
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-top-match-visit' ).show();
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-top-match-edit' ).show();
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-top-match-item-image' )
			.addClass( 'dimmed' );
	}

	bs.extendedSearch.AutocompleteTopMatch.prototype.onMouseLeave = function( e ) {
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-top-match-visit' ).hide();
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-top-match-edit' ).hide();
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-top-match-item-image' )
			.removeClass( 'dimmed' );
	}
} )( mediaWiki, jQuery, blueSpice, document );