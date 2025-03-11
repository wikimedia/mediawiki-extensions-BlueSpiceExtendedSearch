$( () => {
	const searchBar = new bs.extendedSearch.SearchBar( {
		masterFilter: mw.config.get( 'ESMasterFilter' )
	} );

	bs.extendedSearch.Autocomplete._instance = new bs.extendedSearch.Autocomplete( searchBar );

	$( document ).on( 'keydown', ( e ) => {
		// See if is an input or a textarea
		if ( $( e.target ).is( 'input, textarea' ) ) {
			return;
		}
		// or the parents
		if ( $( e.target ).parents( 'input, textarea' ).length > 0 ) {
			return;
		}
		// + exception for VE and CollabPad
		if ( $( e.target ).hasClass( 've-ce-branchNode' ) ) {
			return;
		}

		if ( e.key === '/' ) {
			e.preventDefault();
			searchBar.$searchBox.focus(); // eslint-disable-line no-jquery/no-event-shorthand
		}

	} );
} );
