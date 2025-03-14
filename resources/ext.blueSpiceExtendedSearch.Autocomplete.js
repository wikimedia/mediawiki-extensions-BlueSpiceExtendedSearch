( function ( mw, $, bs ) {
	$( () => {
		bs.config.getDeferred( [
			'UseCompactAutocomplete',
			'AutocompleteConfig',
			'SourceIcons'
		] ).done( ( response ) => {
			// Create new autocomplete and searchBar instance and bind them together
			const autocomplete = new bs.extendedSearch.Autocomplete();
			bs.extendedSearch.Autocomplete._instance = autocomplete;
			const useSubpagePillsAutocomplete = require( './config.json' ).useSubpagePillsAutocomplete;
			const searchBar = new bs.extendedSearch.SearchBar( {
				useSubpagePills: useSubpagePillsAutocomplete,
				masterFilter: mw.config.get( 'ESMasterFilter' )
			} );
			autocomplete.init( {
				searchBar: searchBar,
				compact: response.UseCompactAutocomplete,
				autocompleteConfig: response.AutocompleteConfig,
				sourceIcons: response.SourceIcons
			} );

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
					searchBar.$searchBox.trigger( 'focus' );
				}

			} );
		} );
	} );
}( mediaWiki, jQuery, blueSpice ) );
