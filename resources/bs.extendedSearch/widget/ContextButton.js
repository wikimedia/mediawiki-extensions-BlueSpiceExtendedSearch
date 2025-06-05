bs.util.registerNamespace( 'bs.extendedSearch' );

bs.extendedSearch.ContextButton = function ( cfg ) {
	cfg = cfg || {};

	bs.extendedSearch.ContextButton.parent.call( this, cfg );

	OO.ui.mixin.ButtonElement.call( this, cfg );
	OO.ui.mixin.LabelElement.call( this, cfg );
	bs.extendedSearch.mixin.FilterRemoveButton.call( this, {
		showRemove: true,
		label: cfg.rawText || ''
	} );

	this.$label.addClass( 'oo-ui-buttonElement-button bs-extendedsearch-filter-button-button bs-extendedsearch-context-button' );

	this.$element
		.addClass( 'bs-extendedsearch-filter-button-widget' )
		.append( this.$label, this.$removeButton );
};

OO.inheritClass( bs.extendedSearch.ContextButton, OO.ui.Widget );

OO.mixinClass( bs.extendedSearch.ContextButton, OO.ui.mixin.ButtonElement );
OO.mixinClass( bs.extendedSearch.ContextButton, OO.ui.mixin.LabelElement );
OO.mixinClass( bs.extendedSearch.ContextButton, bs.extendedSearch.mixin.FilterRemoveButton );

bs.extendedSearch.ContextButton.prototype.removeFilter = function () {
	this.emit( 'remove' );
};
