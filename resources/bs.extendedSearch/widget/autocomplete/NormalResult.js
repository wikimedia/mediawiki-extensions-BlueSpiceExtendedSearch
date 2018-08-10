( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteNormalResult = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.type = cfg.suggestion.type;
		this.score = cfg.suggestion.score;

		this.searchTerm = cfg.term || '';

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteNormalResult.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );
		bs.extendedSearch.mixin.AutocompleteHitType.call( this, {
			hitType: cfg.suggestion.typetext,
			rankType: 'normal'
		} );

		this.$element.append( this.$header, this.$type );
		this.$element.on( 'click', this.onResultClick );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item' );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteNormalResult, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteNormalResult, bs.extendedSearch.mixin.AutocompleteHeader );
	OO.mixinClass( bs.extendedSearch.AutocompleteNormalResult, bs.extendedSearch.mixin.AutocompleteHitType );

	bs.extendedSearch.AutocompleteNormalResult.prototype.onResultClick = function( e ) {
		//Anchor may be custom one, coming from backend, so we cannot target more specifically
		var anchor = $( e.target ).find( 'a' );
		window.location = anchor.attr( 'href' );
	}

} )( mediaWiki, jQuery, blueSpice, document );
