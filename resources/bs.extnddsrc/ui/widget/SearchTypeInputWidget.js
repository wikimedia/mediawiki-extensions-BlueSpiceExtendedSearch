bs.util.registerNamespace( 'bs.extnddsrc.ui' );

bs.extnddsrc.ui.SearchTypeInputWidget = function BsVecUiSearchTypeInputWidget ( config ) {
	bs.extnddsrc.ui.SearchTypeInputWidget.super.call( this, config );
	this.inspector = config.inspector;
	this.attribute = config.attribute;
	this.setDisabled( true );
	me = this;
	this.getSearchTypes().done( function( options ) {
		me.addOptions( options );
		me.setDisabled( false );
me.setValue( me.inspector.selectedNode.getAttribute( 'mw' ).attrs[me.attribute.name] || me.attribute.default );
	});
};

OO.inheritClass( bs.extnddsrc.ui.SearchTypeInputWidget, OO.ui.MenuTagMultiselectWidget );

bs.extnddsrc.ui.SearchTypeInputWidget.prototype.getValue = function() {
	var value = bs.extnddsrc.ui.SearchTypeInputWidget.super.prototype.getValue.call( this );
	return value.join( "|" );
};

bs.extnddsrc.ui.SearchTypeInputWidget.prototype.setValue = function( value ) {
	// remove any whitespace around commas
	var value = value.replace( /[\s,]+/g, '|' );
	value = value.split( "|" );
	return bs.extnddsrc.ui.SearchTypeInputWidget.super.prototype.setValue.call( this, value );
};

bs.extnddsrc.ui.SearchTypeInputWidget.prototype.getSearchTypes = function() {
	var dfd = $.Deferred();
	bs.api.store.getData( 'extendedsearch-type' ).done( function( response ) {
		var results = response.results;
		var options = [];
		for ( var i = 0; i < results.length; i++ ) {
			options.push({
				data: results[i],
				label: results[i]
			});
		};
		dfd.resolve( options );
	});
	return dfd.promise();
};
