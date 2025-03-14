( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.mixin.QuietSubpage = function ( subpage ) {
		// Trim-out the quietSubpage from the result titles
		this.titleTrim = subpage + '/';

		const globalSearchButton = new OO.ui.ButtonWidget( {
			framed: false,
			label: mw.message( 'bs-extendedsearch-autocomplete-popup-quiet-subpage-clear' ).text(),
			flags: [ 'primary', 'progressive' ]
		} );

		globalSearchButton.connect( this, {
			click: function () {
				this.emit( 'quietSubpageRemoved' );
			}
		} );
		this.$quietSubpage = $( '<div>' ).addClass( 'quiet-subpage' ).append(
			$( '<span>' ).addClass( 'text' ).html(
				mw.message( 'bs-extendedsearch-autocomplete-popup-quiet-subpage', subpage ).text()
			),
			globalSearchButton.$element
		);

	};
}( mediaWiki, jQuery, blueSpice ) );
