( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteSecondaryResult = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.uri = cfg.suggestion.uri;
		this.type = cfg.suggestion.type;
		this.editLink = cfg.suggestion.edit_uri || null;

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteSecondaryResult.parent.call( this, cfg );

		this.$header = $( '<a>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-item-header' )
			.attr( 'href', this.uri )
			.html( this.basename );

		this.$type = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-item-type' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-type', this.type ).plain() );

		this.$element.append( this.$header, this.$type );

		if( this.editLink ) {
			this.$element.append(
				$( '<a>' )
					.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-edit' )
					.attr( 'href', this.editLink )
					.html( mw.message( 'bs-extendedsearch-autocomplete-result-edit-label' ).plain() )
			);
		}

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-item' );
		this.$element.on( 'mouseenter', this.onMouseEnter.bind( this ) );
		this.$element.on( 'mouseleave', this.onMouseLeave.bind( this ) );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteSecondaryResult, OO.ui.Widget );

	bs.extendedSearch.AutocompleteSecondaryResult.prototype.onMouseEnter = function( e ) {
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-secondary-edit' ).show();
	}

	bs.extendedSearch.AutocompleteSecondaryResult.prototype.onMouseLeave = function( e ) {
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-secondary-edit' ).hide();
	}

} )( mediaWiki, jQuery, blueSpice, document );