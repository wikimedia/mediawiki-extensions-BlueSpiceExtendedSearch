(function( mw, $, bs, d, undefined ){
	var searchField = OO.ui.infuse( 'bs-es-tf-search' );
	var curQueryData = bs.extendedSearch.getHash();
	searchField.setValue( curQueryData.q || '' );
	searchField.on( 'change', function ( value ) {
		updateQueryHash( value );
	});

	var searchButton = OO.ui.infuse( 'bs-es-btn-search' );
	searchButton.on( 'click', function () {
		updateQueryHash( searchField.getValue() );
	});

	function updateQueryHash( term ) {
		var queryData = bs.extendedSearch.getHash();
		var newQuery = {
			'q' : term
		};

		bs.extendedSearch.setHash(
			$.extend( queryData, newQuery )
		);
	}

	var api = new mw.Api();
	function _execSearch() {
		searchField.popPending();
		searchField.pushPending();

		var queryData = bs.extendedSearch.getHash();

		api.abort();
		api.get( $.extend(
			queryData,
			{
				'action': 'bs-extendedsearch-query'
			}
		))
		.done( function() {
			searchField.popPending();
		});
	}

	bs.extendedSearch.SearchCenter = {
		execSearch: _execSearch
	};

})( mediaWiki, jQuery, blueSpice, document );

jQuery(window).on( 'hashchange', function() {
	bs.extendedSearch.SearchCenter.execSearch();
});

jQuery( function() {
	bs.extendedSearch.SearchCenter.execSearch();
});