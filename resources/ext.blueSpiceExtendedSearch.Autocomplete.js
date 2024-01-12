( function( mw, $, bs, d, undefined ){
	$( function() {
		bs.config.getDeferred( [
			'UseCompactAutocomplete',
			'AutocompleteConfig',
			'SourceIcons'
		] ).done( function( response ) {
			//Create new autocomplete and searchBar instance and bind them together
			var autocomplete = new bs.extendedSearch.Autocomplete();
			var useSubpagePillsAutocomplete = require( './config.json' ).useSubpagePillsAutocomplete;
			var searchBar = new bs.extendedSearch.SearchBar( {
				useSubpagePills: useSubpagePillsAutocomplete,
				masterFilter: mw.config.get( 'ESMasterFilter' )
			} );
			autocomplete.init( {
				searchBar: searchBar,
				compact: response.UseCompactAutocomplete,
				autocompleteConfig: response.AutocompleteConfig,
				sourceIcons: response.SourceIcons
			} );

			$( document ).on( 'keydown', function ( e ) {
				if( e.key === '/' ) {
					e.preventDefault();
					searchBar.$searchBox.focus();
				}

			});
		} );
	} );
} )( mediaWiki, jQuery, blueSpice, document );
