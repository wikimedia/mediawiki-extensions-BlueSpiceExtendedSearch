( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompletePrimaryResult = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.uri = cfg.suggestion.uri;
		this.type = cfg.suggestion.type;
		this.score = cfg.suggestion.score;
		this.editLink = cfg.suggestion.edit_uri || null;
		this.iconUri = bs.extendedSearch.Autocomplete.getIconPath( this.type );

		this.searchTerm = cfg.term || '';

		this.boldSearchTerm();

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompletePrimaryResult.parent.call( this, cfg );

		//Using div for better size handling cross-browser
		this.$icon = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item-image' )
			.attr( 'style', "background-image: url(" + this.iconUri + ")" );

		this.$header = $( '<a>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item-header' )
			.attr( 'href', this.uri )
			.html( this.basename );

		this.$type = $( '<span>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item-type' )
			.html( mw.message( 'bs-extendedsearch-autocomplete-result-type', this.type ).plain() );	

		this.$element.append( this.$header, /*this.$icon,*/ this.$type );

		if( this.editLink ) {
			this.$element.append(
				$( '<a>' )
					.addClass( 'bs-extendedsearch-autocomplete-popup-primary-edit' )
					.attr( 'href', this.editLink )
					.html( mw.message( 'bs-extendedsearch-autocomplete-result-edit-label' ).plain() )
			);
		}

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item' );
		if( this.score >= 7 ) {
			this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-featured' );
		}

		this.$element.on( 'mouseenter', this.onMouseEnter.bind( this ) );
		this.$element.on( 'mouseleave', this.onMouseLeave.bind( this ) );
	}

	OO.inheritClass( bs.extendedSearch.AutocompletePrimaryResult, OO.ui.Widget );

	//Bolds out search term in the result title
	bs.extendedSearch.AutocompletePrimaryResult.prototype.boldSearchTerm = function() {
		var re = new RegExp( "(" + this.searchTerm + ")", "gi" );
		this.basename = this.basename.replace( re, "<b>$1</b>" );
	}

	//Shows Edit button (if possible) when mouseover the result item
	bs.extendedSearch.AutocompletePrimaryResult.prototype.onMouseEnter = function( e ) {
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-primary-edit' ).show();
	}

	bs.extendedSearch.AutocompletePrimaryResult.prototype.onMouseLeave = function( e ) {
		this.$element.find( '.bs-extendedsearch-autocomplete-popup-primary-edit' ).hide();
	}

} )( mediaWiki, jQuery, blueSpice, document );