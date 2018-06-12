( function( mw, $, bs, d, undefined ){
	//Create new autocomplete and searchBar instance and bind them together
	var autocomplete = new bs.extendedSearch.Autocomplete();
	var searchBar = new bs.extendedSearch.SearchBar();

	autocomplete.init( {searchBar:searchBar} );

} )( mediaWiki, jQuery, blueSpice, document );
