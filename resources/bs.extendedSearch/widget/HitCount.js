( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.HitCountWidget = function( cfg ) {
		cfg = cfg || {};

		//this.icon = cfg.icon;
		this.term = cfg.term || '';
		this.count = cfg.count || 0;

		this.$element = $( '<p>' )
			.html( mediaWiki.message( 'bs-extendedsearch-search-center-hitcount-widget', this.count, this.term ).parse() );
	}

	OO.inheritClass( bs.extendedSearch.HitCountWidget, OO.ui.Widget );
} )( mediaWiki, jQuery, blueSpice, document );