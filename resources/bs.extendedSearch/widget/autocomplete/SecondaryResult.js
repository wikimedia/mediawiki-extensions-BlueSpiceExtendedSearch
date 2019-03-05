( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteSecondaryResult = function( cfg ) {
		cfg = cfg || {};

		this.popup = cfg.popup;

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteSecondaryResult.parent.call( this, {} );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );

		this.$element.append( this.$header, this.$type );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-item' );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteSecondaryResult, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteSecondaryResult, bs.extendedSearch.mixin.AutocompleteHeader );

} )( mediaWiki, jQuery, blueSpice, document );