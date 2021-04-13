( function( mw, $, bs, d, undefined ){
	$( '.bs-tagsearch-cnt' ).each( function( key, value ) {
		var $searchField = $( value );
		var $searchInput = $searchField.find( '.bs-tagsearch-searchfield' );
		var $lookupInput = $searchField.find( 'input[name="lookup"]' );

		var lookupCfg = JSON.parse( $lookupInput.val() );

		bs.config.getDeferred( [
			'AutocompleteConfig',
			'SourceIcons'
		] ).done( function( response ) {
			var autocomplete = new bs.extendedSearch.Autocomplete();
			var searchBar = new bs.extendedSearch.SearchBar( {
				useNamespacePills: false,
				cntId: $searchField.attr( 'id' ),
				inputId: $searchInput.attr( 'id' )
			} );

			autocomplete.init( {
				searchBar: searchBar,
				autocompleteConfig: response.AutocompleteConfig,
				compact: true,
				sourceIcons: response.SourceIcons,
				lookupConfig: lookupCfg
			} );
		} );

	} );
} )( mediaWiki, jQuery, blueSpice, document );