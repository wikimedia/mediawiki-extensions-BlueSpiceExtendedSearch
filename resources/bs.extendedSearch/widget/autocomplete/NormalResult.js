( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.AutocompleteNormalResult = function( cfg ) {
		cfg = cfg || {};

		this.basename = cfg.suggestion.basename;
		this.type = cfg.suggestion.type;
		this.score = cfg.suggestion.score;

		this.searchTerm = cfg.term || '';
		this.popup = cfg.popup;

		this.$element = $( '<div>' );

		bs.extendedSearch.AutocompleteNormalResult.parent.call( this, cfg );
		bs.extendedSearch.mixin.AutocompleteHeader.call( this, cfg.suggestion );

		this.$element.append( this.$header, this.$type );
		this.$element.on( 'click', this.onResultClick );

		this.$element.addClass( 'bs-extendedsearch-autocomplete-popup-primary-item' );
	}

	OO.inheritClass( bs.extendedSearch.AutocompleteNormalResult, OO.ui.Widget );
	OO.mixinClass( bs.extendedSearch.AutocompleteNormalResult, bs.extendedSearch.mixin.AutocompleteHeader );

	bs.extendedSearch.AutocompleteNormalResult.prototype.onResultClick = function( e ) {
		var $target = $( e.target );
		if( $target.hasClass( 'bs-extendedsearch-autocomplete-popup-primary-item' ) === false ) {
			return;
		}
		//Anchor may be custom one, coming from backend, so we cannot target more specifically
		var $anchor = $target.find( 'a' );
		if( $anchor ) {
			window.location = $anchor.attr( 'href' );
		}
	}

} )( mediaWiki, jQuery, blueSpice, document );
