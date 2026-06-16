bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

bs.extendedSearch.mixin.FilterRemoveButton = function () {
};

OO.initClass( bs.extendedSearch.mixin.FilterRemoveButton );

bs.extendedSearch.mixin.FilterRemoveButton.prototype.removeFilter = function () {
	this.$element.trigger( 'removeWidgetClick', {
		filterId: this.id,
		values: this.selectedOptions,
		options: this.options
	} );
};
