( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.OptionsDialog = function( cfg, toolsPanel ) {
		cfg = cfg || {};

		this.options = cfg.options;
		this.toolsPanel = toolsPanel;

		bs.extendedSearch.OptionsDialog.super.call( this, cfg );
	}

	OO.inheritClass( bs.extendedSearch.OptionsDialog, OO.ui.ProcessDialog );

	bs.extendedSearch.OptionsDialog.static.name = 'optionsDialog';

	bs.extendedSearch.OptionsDialog.static.title = mw.message( 'bs-extendedsearch-search-center-options-dialog-title' ).plain();

	bs.extendedSearch.OptionsDialog.static.actions = [
		{
			action: 'save',
			label: mw.message( 'bs-extendedsearch-search-center-options-dialog-button-apply-label' ).plain(),
			flags: 'primary',
			disabled: false
		},
		{
			label: mw.message( 'bs-extendedsearch-search-center-dialog-button-cancel-label' ).plain(),
			flags: 'safe'
		}

	];

	bs.extendedSearch.OptionsDialog.prototype.initialize = function() {
		bs.extendedSearch.OptionsDialog.super.prototype.initialize.call( this );

		this.booklet = new OO.ui.BookletLayout( {
			outlined: true
		} );

		this.optionPages = ['pageSize', 'sortBy', 'sortOrder'];

		var pageSizeLayout = new bs.extendedSearch.PageSizeLayout( 'pageSize', { pageSizeOptions: this.options.pageSize } );
		var sortByLayout = new bs.extendedSearch.SortByLayout( 'sortBy', { sortByOptions: this.options.sortBy } );
		var sortOrderLayout = new bs.extendedSearch.SortOrderLayout( 'sortOrder', { sortOrderOptions: this.options.sortOrder } );

		this.booklet.addPages( [pageSizeLayout, sortByLayout, sortOrderLayout] );

		this.$body.append( this.booklet.$element );
	}

	bs.extendedSearch.OptionsDialog.prototype.getBodyHeight = function () {
		return this.booklet.$element.outerHeight() + 300;
	};

	bs.extendedSearch.OptionsDialog.prototype.getActionProcess = function ( action ) {
		var me = this;

		if( action === 'save' ) {
			return new OO.ui.Process( function() {
				var results = {};

				for( pageIdx in me.optionPages ) {
					var pageName = me.optionPages[pageIdx];
					var page = me.booklet.getPage( pageName );
					var value = page.getValue();
					results[pageName] = value;
				}
				me.toolsPanel.applyValuesFromOptionsDialog( results );

				return me.close( { action: action } );
			} );
		}

		return bs.extendedSearch.OptionsDialog.super.prototype.getActionProcess.call( this, action );
	};


	//PAGE SIZE LAYOUT
	bs.extendedSearch.PageSizeLayout = function( name, cfg ) {
		bs.extendedSearch.PageSizeLayout.parent.call( this, name, cfg );

		this.pageSizeInput = new OO.ui.RadioSelectInputWidget( cfg.pageSizeOptions );

		this.$element.append(
			this.pageSizeInput.$element
		);
	}

	OO.inheritClass( bs.extendedSearch.PageSizeLayout, OO.ui.PageLayout );

	bs.extendedSearch.PageSizeLayout.prototype.setupOutlineItem = function() {
		this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-page-size').plain() );
	}

	bs.extendedSearch.PageSizeLayout.prototype.getValue = function() {
		return this.pageSizeInput.value;
	}

	//SORTING FIELD PAGE
	bs.extendedSearch.SortByLayout = function( name, cfg ) {
		bs.extendedSearch.SortByLayout.parent.call( this, name, cfg );

		this.sortByInput = new OO.ui.RadioSelectInputWidget( cfg.sortByOptions );

		this.$element.append(
			this.sortByInput.$element
		);
	}

	OO.inheritClass( bs.extendedSearch.SortByLayout, OO.ui.PageLayout );

	bs.extendedSearch.SortByLayout.prototype.setupOutlineItem = function() {
		this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-sort-by').plain() );
	}

	bs.extendedSearch.SortByLayout.prototype.getValue = function() {
		return [ this.sortByInput.value ];
	}

	//SORT ORDER LAYOUT
	bs.extendedSearch.SortOrderLayout = function( name, cfg ) {
		bs.extendedSearch.SortOrderLayout.parent.call( this, name, cfg );

		this.sortOrderInput = new OO.ui.RadioSelectInputWidget( cfg.sortOrderOptions );

		this.$element.append(
			this.sortOrderInput.$element
		);
	}

	OO.inheritClass( bs.extendedSearch.SortOrderLayout, OO.ui.PageLayout );

	bs.extendedSearch.SortOrderLayout.prototype.setupOutlineItem = function() {
		this.outlineItem.setLabel( mw.message( 'bs-extendedsearch-search-center-options-sort-order').plain() );
	}

	bs.extendedSearch.SortOrderLayout.prototype.getValue = function() {
		return this.sortOrderInput.value;
	}

} )( mediaWiki, jQuery, blueSpice, document );
