( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteSecondaryResult = function( cfg ) {
		cfg = cfg || {};

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteSecondaryResult.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );
		bs.extendedSearch.mixin.AutocompleteHitType.call( this, {
			hitType: cfg.suggestion.type,
			rankType: 'secondary'
		} );

		this.$element.append( this.$header, this.$type );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-secondary-item' );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteSecondaryResult, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteSecondaryResult, bs.extendedSearch.mixin.AutocompleteHeader );
	OO.mixinClass( bs.extendedSearch.AutocompleteSecondaryResult, bs.extendedSearch.mixin.AutocompleteHitType );

} )( mediaWiki, jQuery, blueSpice, document );