bs.extendedSearch.OptionStorage = function () {
	this.storageKey = 'bs-extendedsearch-options';
};

OO.initClass( bs.extendedSearch.OptionStorage );

bs.extendedSearch.OptionStorage.prototype.getOptions = function () {
	const raw = localStorage.getItem( this.storageKey ); // eslint-disable-line mediawiki/no-storage
	if ( !raw ) {
		return {};
	}
	return JSON.parse( raw );
};

bs.extendedSearch.OptionStorage.prototype.setOptions = function ( values ) {
	// Set local storage
	localStorage.setItem( this.storageKey, JSON.stringify( values ) ); // eslint-disable-line mediawiki/no-storage
};
