( function ( mw, $, bs ) {
	bs.extendedSearch.HitCountWidget = function ( cfg ) {
		cfg = cfg || {};

		this.term = cfg.term || '';
		this.count = cfg.count || 0;
		this.total_approximated = cfg.total_approximated; // eslint-disable-line camelcase
		if ( cfg.spellCheck && cfg.spellCheck.action === 'replaced' ) {
			this.term = cfg.spellCheck.alternative.term;
		}

		this.$element = $( '<div>' )
			.addClass( 'bs-extendedsearch-search-center-hitcount' )
			.attr( 'aria-live', 'polite' );
	};

	OO.inheritClass( bs.extendedSearch.HitCountWidget, OO.ui.Widget );

	bs.extendedSearch.HitCountWidget.prototype.init = function () {
		let messageKey = 'bs-extendedsearch-search-center-hitcount-widget';
		if ( this.total_approximated ) {
			messageKey = 'bs-extendedsearch-search-center-hitcount-widget-approximately';
		}

		let message = mw.message( // eslint-disable-line mediawiki/msg-doc
			messageKey,
			this.count
		).escaped();
		message = message.replace( '$2', '<b>' + mw.html.escape( this.term ) + '</b>' );
		this.$element.html( message );
	};

}( mediaWiki, jQuery, blueSpice ) );
