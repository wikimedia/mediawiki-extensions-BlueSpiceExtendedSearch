bs.extendedSearch.OptionsDialog = function ( cfg, toolsPanel ) {
	cfg = cfg || {};

	this.options = cfg;
	this.toolsPanel = toolsPanel;

	bs.extendedSearch.OptionsDialog.super.call( this, cfg );
};

OO.inheritClass( bs.extendedSearch.OptionsDialog, OO.ui.ProcessDialog );

bs.extendedSearch.OptionsDialog.static.name = 'optionsDialog';

bs.extendedSearch.OptionsDialog.static.title = mw.message( 'bs-extendedsearch-search-center-options-dialog-title' ).text();

bs.extendedSearch.OptionsDialog.static.actions = [
	{
		action: 'save',
		label: mw.message( 'bs-extendedsearch-search-center-options-dialog-button-apply-label' ).text(),
		flags: [ 'primary', 'progressive' ]
	},
	{
		title: mw.message( 'bs-extendedsearch-search-center-dialog-button-cancel-label' ).text(),
		flags: [ 'safe', 'close' ]
	}
];

bs.extendedSearch.OptionsDialog.prototype.initialize = function () {
	bs.extendedSearch.OptionsDialog.super.prototype.initialize.call( this );

	this.booklet = new OO.ui.BookletLayout( {
		outlined: true
	} );

	this.optionPages = [ 'pageSize', 'sorting' ];

	const pageSizeLayout = new bs.extendedSearch.PageSizeLayout( 'pageSize', { pageSizeOptions: this.options.pageSize } );
	const sortingLayout = new bs.extendedSearch.SortingLayout( 'sorting', {
		sortByOptions: this.options.sortBy,
		sortOrderOptions: this.options.sortOrder
	} );

	this.booklet.addPages( [ pageSizeLayout, sortingLayout ] );
	this.booklet.setPage( 'pageSize' );

	this.$body.append( this.booklet.$element );
};

bs.extendedSearch.OptionsDialog.prototype.getBodyHeight = function () {
	return this.booklet.$element.outerHeight() + 300;
};

bs.extendedSearch.OptionsDialog.prototype.getActionProcess = function ( action ) {
	const me = this;

	if ( action === 'save' ) {
		return new OO.ui.Process( () => {
			let results = {};

			for ( let i = 0; i < me.optionPages.length; i++ ) {
				const page = me.booklet.getPage( me.optionPages[ i ] );
				results = Object.assign( results, page.getValues() );
			}
			me.toolsPanel.updateSearchOptions( results );

			return me.close( { action: action } );
		} );
	}

	return bs.extendedSearch.OptionsDialog.super.prototype.getActionProcess.call( this, action );
};

// PAGE SIZE LAYOUT
bs.extendedSearch.PageSizeLayout = function ( name, cfg ) {
	bs.extendedSearch.PageSizeLayout.parent.call( this, name, cfg );

	this.pageSizeInput = new OO.ui.RadioSelectInputWidget( cfg.pageSizeOptions );

	this.field = new OO.ui.FieldLayout( this.pageSizeInput, {
		label: mw.message( 'bs-extendedsearch-search-center-options-page-size-label' ).text(),
		align: 'top'
	} );

	this.$element.append( this.field.$element );
};

OO.inheritClass( bs.extendedSearch.PageSizeLayout, OO.ui.PageLayout );

bs.extendedSearch.PageSizeLayout.prototype.setupOutlineItem = function () {
	this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-page-size' ).text() );
};

bs.extendedSearch.PageSizeLayout.prototype.getValues = function () {
	return { pageSize: this.pageSizeInput.value };
};

// SORTING LAYOUT - combines sort field and sort order on a single page
bs.extendedSearch.SortingLayout = function ( name, cfg ) {
	bs.extendedSearch.SortingLayout.parent.call( this, name, cfg );

	this.sortByInput = new OO.ui.RadioSelectInputWidget( cfg.sortByOptions );
	this.sortOrderInput = new OO.ui.RadioSelectInputWidget( cfg.sortOrderOptions );

	this.sortByField = new OO.ui.FieldLayout( this.sortByInput, {
		label: mw.message( 'bs-extendedsearch-search-center-options-sort-by' ).text(),
		align: 'top'
	} );
	this.sortOrderField = new OO.ui.FieldLayout( this.sortOrderInput, {
		label: mw.message( 'bs-extendedsearch-search-center-options-sort-order' ).text(),
		align: 'top'
	} );

	this.$element.append( this.sortByField.$element, this.sortOrderField.$element );
};

OO.inheritClass( bs.extendedSearch.SortingLayout, OO.ui.PageLayout );

bs.extendedSearch.SortingLayout.prototype.setupOutlineItem = function () {
	this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-sorting' ).text() );
};

bs.extendedSearch.SortingLayout.prototype.getValues = function () {
	return {
		sortBy: [ this.sortByInput.value ],
		sortOrder: this.sortOrderInput.value
	};
};
