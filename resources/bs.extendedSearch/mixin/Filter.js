( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.mixin.FilterRemoveButton = function( cfg ) {
		cfg = cfg || {};

		this.showRemove = cfg.showRemove || false;
		if( !this.showRemove ) {
			return null;
		}

		var button = new OO.ui.ButtonWidget( {
			indicator: 'clear'
		} );

		button.connect( this, { click: 'removeFilter' } );

		this.$removeButton = button.$element;
		this.$removeButton.addClass( 'bs-extendedsearch-filter-button-remove' );
	}

	OO.initClass( bs.extendedSearch.mixin.FilterRemoveButton );

	bs.extendedSearch.mixin.FilterRemoveButton.prototype.removeFilter = function() {
		this.$element.trigger( 'removeWidgetClick', {
			filterId: this.id,
			values: this.selectedOptions,
			options: this.options
		} );
	}
} )( mediaWiki, jQuery, blueSpice, document );

