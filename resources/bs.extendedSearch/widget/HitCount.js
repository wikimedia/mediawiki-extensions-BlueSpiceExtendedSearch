( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.HitCountWidget = function( cfg ) {
		cfg = cfg || {};

		this.term = cfg.term || '';
		this.count = cfg.count || 0;
		this.total_approximated = cfg.total_approximated;
		if ( cfg.spellCheck && cfg.spellCheck.action === 'replaced' ) {
			this.term = cfg.spellCheck.alternative.term;
		}

		var messageKey = 'bs-extendedsearch-search-center-hitcount-widget';
		if( this.total_approximated ) {
			messageKey = 'bs-extendedsearch-search-center-hitcount-widget-approximately';
		}

		var message = mw.message(
			messageKey,
			this.count
		).escaped();
		message = message.replace( '$2', "<b>" + mw.html.escape( this.term ) + "</b>" );
		this.$counter = $( '<p>' )
			.html( message );

		this.$element = $( '<div>' ).addClass( 'bs-extendedsearch-search-center-hitcount' ).append( this.$counter );
	};

	OO.inheritClass( bs.extendedSearch.HitCountWidget, OO.ui.Widget );

} )( mediaWiki, jQuery, blueSpice, document );
