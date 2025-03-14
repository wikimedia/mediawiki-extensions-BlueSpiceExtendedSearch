( function ( $, bs ) {
	$( '.bs-tagsearch-cnt' ).each( ( key, value ) => {
		const $searchField = $( value );
		const $searchInput = $searchField.find( '.bs-tagsearch-searchfield' );
		const $lookupInput = $searchField.find( 'input[name="lookup"]' );

		const lookupCfg = JSON.parse( $lookupInput.val() );

		bs.config.getDeferred( [
			'AutocompleteConfig',
			'SourceIcons'
		] ).done( ( response ) => {
			const autocomplete = new bs.extendedSearch.Autocomplete();
			const searchBar = new bs.extendedSearch.SearchBar( {
				useNamespacePills: false,
				cntId: $searchField.attr( 'id' ),
				inputId: $searchInput.attr( 'id' ),
				showRecentlyFound: false
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
}( jQuery, blueSpice ) );
