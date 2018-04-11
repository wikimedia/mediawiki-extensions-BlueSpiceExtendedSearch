( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompletePrimaryResult = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.type = cfg.suggestion.type;
		this.score = cfg.suggestion.score;
		//this.iconUri = bs.extendedSearch.Autocomplete.getIconPath( this.type );

		this.searchTerm = cfg.term || '';

		this.boldSearchTerm();

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompletePrimaryResult.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );
		bs.extendedSearch.mixin.AutocompleteHitType.call( this, {
			hitType: cfg.suggestion.type,
			rankType: 'primary'
		} );

		//Using div for better size handling cross-browser
		/*this.$icon = $( '<div>' )
			.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item-image' )
			.attr( 'style', "background-image: url(" + this.iconUri + ")" );*/


		this.$element.append( this.$header, /*this.$icon,*/ this.$type );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item' );
		if( this.score >= 7 ) {
			this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-featured' );
		}
	}

	OO.inheritClass( bs.extendedSearch.AutocompletePrimaryResult, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompletePrimaryResult, bs.extendedSearch.mixin.AutocompleteHeader );
	OO.mixinClass( bs.extendedSearch.AutocompletePrimaryResult, bs.extendedSearch.mixin.AutocompleteHitType );

	//Bolds out search term in the result title
	bs.extendedSearch.AutocompletePrimaryResult.prototype.boldSearchTerm = function() {
		var re = new RegExp( "(" + this.searchTerm + ")", "gi" );
		this.basename = this.basename.replace( re, "<b>$1</b>" );
	}

} )( mediaWiki, jQuery, blueSpice, document );