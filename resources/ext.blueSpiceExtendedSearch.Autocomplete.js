( function( mw, $, bs, d, undefined ){
	//Create new autocomplete and searchBar instance and bind them together
	var autocomplete = new bs.extendedSearch.Autocomplete();
	var searchBar = new bs.extendedSearch.SearchBar();

	var useCompact = mw.config.get( 'ESUseCompactAutocomplete' );
	autocomplete.init( {searchBar:searchBar, compact: useCompact } );

} )( mediaWiki, jQuery, blueSpice, document );
