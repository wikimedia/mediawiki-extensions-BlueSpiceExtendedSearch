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
		flags: 'primary',
		disabled: false
	},
	{
		label: mw.message( 'bs-extendedsearch-search-center-dialog-button-cancel-label' ).text(),
		flags: 'safe'
	}

];

bs.extendedSearch.OptionsDialog.prototype.initialize = function () {
	bs.extendedSearch.OptionsDialog.super.prototype.initialize.call( this );

	this.booklet = new OO.ui.BookletLayout( {
		outlined: true
	} );

	this.optionPages = [ 'pageSize', 'sortBy', 'sortOrder' ];

	const pageSizeLayout = new bs.extendedSearch.PageSizeLayout( 'pageSize', { pageSizeOptions: this.options.pageSize } );
	const sortByLayout = new bs.extendedSearch.SortByLayout( 'sortBy', { sortByOptions: this.options.sortBy } );
	const sortOrderLayout = new bs.extendedSearch.SortOrderLayout( 'sortOrder', { sortOrderOptions: this.options.sortOrder } );

	this.booklet.addPages( [ pageSizeLayout, sortByLayout, sortOrderLayout ] );

	this.$body.append( this.booklet.$element );
};

bs.extendedSearch.OptionsDialog.prototype.getBodyHeight = function () {
	return this.booklet.$element.outerHeight() + 300;
};

bs.extendedSearch.OptionsDialog.prototype.getActionProcess = function ( action ) {
	const me = this;

	if ( action === 'save' ) {
		return new OO.ui.Process( () => {
			const results = {};

			for ( let i = 0; i < me.optionPages.length; i++ ) {
				const pageName = me.optionPages[ i ];
				const page = me.booklet.getPage( pageName );
				const value = page.getValue();
				results[ pageName ] = value;
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

	this.$element.append(
		this.pageSizeInput.$element
	);
};

OO.inheritClass( bs.extendedSearch.PageSizeLayout, OO.ui.PageLayout );

bs.extendedSearch.PageSizeLayout.prototype.setupOutlineItem = function () {
	this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-page-size' ).text() );
};

bs.extendedSearch.PageSizeLayout.prototype.getValue = function () {
	return this.pageSizeInput.value;
};

// SORTING FIELD PAGE
bs.extendedSearch.SortByLayout = function ( name, cfg ) {
	bs.extendedSearch.SortByLayout.parent.call( this, name, cfg );

	this.sortByInput = new OO.ui.RadioSelectInputWidget( cfg.sortByOptions );

	this.$element.append(
		this.sortByInput.$element
	);
};

OO.inheritClass( bs.extendedSearch.SortByLayout, OO.ui.PageLayout );

bs.extendedSearch.SortByLayout.prototype.setupOutlineItem = function () {
	this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-sort-by' ).text() );
};

bs.extendedSearch.SortByLayout.prototype.getValue = function () {
	return [ this.sortByInput.value ];
};

// SORT ORDER LAYOUT
bs.extendedSearch.SortOrderLayout = function ( name, cfg ) {
	bs.extendedSearch.SortOrderLayout.parent.call( this, name, cfg );

	this.sortOrderInput = new OO.ui.RadioSelectInputWidget( cfg.sortOrderOptions );

	this.$element.append(
		this.sortOrderInput.$element
	);
};

OO.inheritClass( bs.extendedSearch.SortOrderLayout, OO.ui.PageLayout );

bs.extendedSearch.SortOrderLayout.prototype.setupOutlineItem = function () {
	this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-sort-order' ).text() );
};

bs.extendedSearch.SortOrderLayout.prototype.getValue = function () {
	return this.sortOrderInput.value;
};
