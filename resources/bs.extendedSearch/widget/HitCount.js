( function ( mw, $, bs ) {
	bs.extendedSearch.HitCountWidget = function ( cfg ) {
		cfg = cfg || {};

		this.count = cfg.count || 0;
		this.total_approximated = cfg.total_approximated; // eslint-disable-line camelcase

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

		let message = mw.message( messageKey, this.count ).escaped(); // eslint-disable-line mediawiki/msg-doc
		// BC for old messages
		message = message.replace( '$2', '' );
		this.$element.html( message );
	};

}( mediaWiki, jQuery, blueSpice ) );
