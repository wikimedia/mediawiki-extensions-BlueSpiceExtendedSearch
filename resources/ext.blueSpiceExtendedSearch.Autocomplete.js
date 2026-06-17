$( () => {
	const searchBar = new bs.extendedSearch.SearchBar( {
		masterFilter: mw.config.get( 'ESMasterFilter' )
	} );

	const cfg = {};

	const contexts = mw.config.get( 'ESContexts' ) || {};
	const defaultContext = mw.config.get( 'ESDefaultContext' );
	if ( defaultContext && contexts[ defaultContext ] ) {
		const contextData = {
			key: defaultContext,
			definition: JSON.parse( contexts[ defaultContext ].definition || '{}' ),
			text: contexts[ defaultContext ].text,
			showCustomPill: contexts[ defaultContext ].showCustomPill
		};
		cfg.lookupConfig = { context: contextData };
	}
	bs.extendedSearch.Autocomplete._instance = new bs.extendedSearch.Autocomplete( searchBar, cfg );

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

		if ( !mw.user.options.get( 'searchShortcut' ) ) {
			return;
		}
		if ( e.key === '/' ) {
			e.preventDefault();
			searchBar.$searchBox.focus(); // eslint-disable-line no-jquery/no-event-shorthand
		}

	} );
} );
