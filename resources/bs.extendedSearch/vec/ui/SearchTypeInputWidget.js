bs.util.registerNamespace( 'bs.extendedSearch.vec.ui' );

bs.extendedSearch.vec.ui.SearchTypeInputWidget = function BsVecUiSearchTypeInputWidget( config ) {
	bs.extendedSearch.vec.ui.SearchTypeInputWidget.super.call( this, config );
	this.inspector = config.inspector;
	this.attribute = config.attribute;
	this.setDisabled( true );

	this.getSearchTypes().done( ( options ) => {
		this.addOptions( options );
		this.setDisabled( false );
		this.setValue(
			this.inspector.selectedNode.getAttribute( 'mw' ).attrs[ this.attribute.name ] || this.attribute.default
		);
	} );
};

OO.inheritClass( bs.extendedSearch.vec.ui.SearchTypeInputWidget, OO.ui.MenuTagMultiselectWidget );

bs.extendedSearch.vec.ui.SearchTypeInputWidget.prototype.getValue = function () {
	const value = bs.extendedSearch.vec.ui.SearchTypeInputWidget.parent.prototype.getValue.call( this );
	return value.join( '|' );
};

bs.extendedSearch.vec.ui.SearchTypeInputWidget.prototype.setValue = function ( value ) {
	if ( !value || Array.isArray( value ) ) {
		return;
	}

	// remove any whitespace around commas
	value = value.replace( /[\s,]+/g, '|' );
	value = value.split( '|' );
	return bs.extendedSearch.vec.ui.SearchTypeInputWidget.parent.prototype.setValue.call( this, value );
};

bs.extendedSearch.vec.ui.SearchTypeInputWidget.prototype.getSearchTypes = function () {
	const dfd = $.Deferred();
	bs.api.store.getData( 'extendedsearch-type' ).done( ( response ) => {
		const results = response.results;
		const options = [];
		for ( let i = 0; i < results.length; i++ ) {
			options.push( {
				data: results[ i ],
				label: results[ i ]
			} );
		}
		dfd.resolve( options );
	} );

	return dfd.promise();
};
