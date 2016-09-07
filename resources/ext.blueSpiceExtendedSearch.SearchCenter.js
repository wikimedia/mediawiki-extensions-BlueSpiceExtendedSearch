(function( mw, $, d, undefined ){
	var searchButton = OO.ui.infuse( 'bs-es-btn-search' );
	searchButton.on( 'click', function () {
		//Do something
	});

	var searchButton = OO.ui.infuse( 'bs-es-tf-search' );
	var api = new mw.Api();
	searchButton.on( 'change', function ( value ) {
		searchButton.popPending();
		searchButton.pushPending();

		api.abort();
		api.get({
			'action': 'bs-extendedsearch-query',
			'q': value
		})
		.done( function() {
			searchButton.popPending();
		});
	});
})( mediaWiki, jQuery, document );