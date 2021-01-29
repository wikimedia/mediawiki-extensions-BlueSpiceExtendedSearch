( function( mw, $, bs, d, undefined ){
	$( function() {
		bs.config.getDeferred( [
			'UseCompactAutocomplete',
			'AutocompleteConfig',
			'SourceIcons'
		] ).done( function( response ) {
			//Create new autocomplete and searchBar instance and bind them together
			var autocomplete = new bs.extendedSearch.Autocomplete();
			var searchBar = new bs.extendedSearch.SearchBar( {
				useSubpagePills: mw.config.get( 'ESUseSubpagePillsAutocomplete' ),
				masterFilter: mw.config.get( 'ESMasterFilter' )
			} );
			autocomplete.init( {
				searchBar: searchBar,
				compact: response.UseCompactAutocomplete,
				autocompleteConfig: response.AutocompleteConfig,
				sourceIcons: response.SourceIcons
			} );
		} );
	} );
} )( mediaWiki, jQuery, blueSpice, document );
