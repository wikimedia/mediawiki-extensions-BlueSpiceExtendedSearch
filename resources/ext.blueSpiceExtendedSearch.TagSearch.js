$( () => {
	$( '.bs-tagsearch-cnt' ).each( ( key, value ) => {
		const $searchField = $( value );
		const $searchInput = $searchField.find( '.bs-tagsearch-searchfield' );
		const $lookupInput = $searchField.find( 'input[name="lookup"]' );

		const lookupCfg = JSON.parse( $lookupInput.val() );

		const searchBar = new bs.extendedSearch.SearchBar( {
			useNamespacePills: false,
			useSubpagePills: false,
			cntId: $searchField.attr( 'id' ),
			inputId: $searchInput.attr( 'id' ),
			showRecentlyFound: false
		} );

		/* eslint-disable no-new */
		new bs.extendedSearch.Autocomplete( searchBar, {
			lookupConfig: lookupCfg,
			enableSearchContexts: false
		} );
	} );
} );
