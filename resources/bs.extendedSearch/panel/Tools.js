bs.extendedSearch.ToolsPanel = function ( cfg ) {
	bs.extendedSearch.ToolsPanel.parent.call( this, { padded: false, expanded: false } );

	this.lookup = cfg.lookup;
	this.filterData = cfg.filterData;
	this.caller = cfg.caller;
	this.mobile = cfg.mobile || false;
	this.hitCounter = cfg.hitCounter;
	this.pageCreateData = cfg.pageCreateData;

	this.defaultFilters = cfg.defaultFilters || [];
	this.activeFilters = [];
	this.optionStorage = new bs.extendedSearch.OptionStorage();

	this.$element.addClass( 'bs-es-tools' );

	if ( this.mobile ) {
		this.$element.addClass( 'mobile' );
	}
};

OO.inheritClass( bs.extendedSearch.ToolsPanel, OO.ui.PanelLayout );

bs.extendedSearch.ToolsPanel.prototype.init = function () {
	this.toolsContainer = new OO.ui.HorizontalLayout( {
		classes: [ 'bs-es-tools-tools' ]
	} );
	this.$filtersContainer = $( '<div>' ).attr( 'id', 'bs-es-tools-filters' );

	this.addContextPill();
	this.addFiltersFromLookup();
	this.addDefaultFilters();

	const addFilterWidget = new bs.extendedSearch.FilterAddWidget( {
		filterData: this.filterData,
		activeFilters: this.activeFilters
	} );
	addFilterWidget.connect( this, {
		addFilter: 'onAddFilter'
	} );

	const menuButton = new OO.ui.ButtonMenuSelectWidget( {
		icon: 'menu',
		title: mw.message( 'bs-extendedsearch-options-button-label' ).text(),
		menu: {
			horizontalPosition: 'end',
			items: [
				new OO.ui.MenuOptionWidget( {
					data: 'options',
					icon: 'settings',
					label: mw.message( 'bs-extendedsearch-options-button-label' ).text()
				} ),
				new OO.ui.MenuOptionWidget( {
					data: 'export',
					icon: 'upload',
					disable: mw.config.get( 'bsgESUserCanExport' ) === false,
					label: mw.msg( 'bs-extendedsearch-export-button-label' )
				} )
			]
		}
	} );
	menuButton.menu.connect( this, {
		select: function ( item ) {
			if ( !item ) {
				return;
			}
			const data = item.getData();
			if ( data === 'options' ) {
				this.openDialog( new bs.extendedSearch.OptionsDialog( this.getSearchOptionsConfig(), this ) );
			}
			if ( data === 'export' ) {
				this.openExportDialog();
			}
		}
	} );

	this.toolsContainer.$element.append(
		addFilterWidget.$element,
		menuButton.$element
	);

	this.$element.append(
		$( '<div>' ).addClass( 'bs-es-tools-tools-container' ).append(
			this.$filtersContainer,
			this.toolsContainer.$element
		),
		this.hitCounter.$element
	);
};

/**
 * Actually adds FilterWidget element to DOM
 *
 * @param {bs.extendedSearch.FilterWidget} filter
 * @param {string} id
 */
bs.extendedSearch.ToolsPanel.prototype.appendFilter = function ( filter, id ) {
	if ( this.activeFilters.indexOf( id ) !== -1 ) {
		return;
	}
	this.$filtersContainer.append( filter.$element );
};

/**
 * Called from bs.extendedSearch.OptionsDialog.
 * Reads in and applies valus from dialog to the Lookup object
 *
 * @param {Array} values
 */
bs.extendedSearch.ToolsPanel.prototype.updateSearchOptions = function ( values ) {
	this.optionStorage.setOptions( values );
	this.applySearchOptions();
	bs.extendedSearch.SearchCenter.updateQueryHash();
};

bs.extendedSearch.ToolsPanel.prototype.applySearchOptions = function ( lookup ) {
	lookup = lookup || this.lookup;
	const values = this.optionStorage.getOptions();
	const size = values.pageSize || 0;
	lookup.setSize( size );

	const sortBy = values.sortBy || [];
	const sortOrder = values.sortOrder || bs.extendedSearch.Lookup.SORT_ASC;

	let i;
	for ( i = 0; i < this.currentSortFields.length; i++ ) {
		const sortedField = this.currentSortFields[ i ];
		if ( sortBy.indexOf( sortedField ) === -1 ) {
			lookup.removeSort( sortedField );
		}
	}

	for ( i = 0; i < sortBy.length; i++ ) {
		lookup.addSort( sortBy[ i ], sortOrder );
	}
};

/**
 * Converts simple array of sortable fields
 * to array of valid config objects
 */
bs.extendedSearch.ToolsPanel.prototype.setSortableFields = function () {
	const fields = mw.config.get( 'bsgESSortableFields' );
	this.sortableFields = [];
	for ( let i = 0; i < fields.length; i++ ) {
		const field = fields[ i ];

		let label = field.charAt( 0 ).toUpperCase() + field.slice( 1 );
		if ( mw.message( 'bs-extendedsearch-searchcenter-sort-field-' + field ).exists() ) { // eslint-disable-line mediawiki/msg-doc
			label = mw.message( 'bs-extendedsearch-searchcenter-sort-field-' + field ).text(); // eslint-disable-line mediawiki/msg-doc
		}

		this.sortableFields.push(
			{
				data: field,
				label: label
			}
		);
	}
};

/**
 * Gets current sort fields and order from Lookup object
 * and converts it to simple array usable in dialog
 */
bs.extendedSearch.ToolsPanel.prototype.setCurrentSortFields = function () {
	const optionsFromStorage = this.optionStorage.getOptions();
	if ( optionsFromStorage.sortBy ) {
		this.currentSortFields = optionsFromStorage.sortBy;
		this.currentSortOrder = optionsFromStorage.sortOrder || bs.extendedSearch.Lookup.SORT_DESC;
		return;
	}
	const sortedFields = [];
	let sortOrder = '';
	const sort = this.lookup.getSort();
	for ( let i = 0; i < sort.length; i++ ) {
		const field = sort[ i ];
		for ( const fieldName in field ) {
			if ( !field.hasOwnProperty( fieldName ) ) {
				continue;
			}
			sortedFields.push( fieldName );
			sortOrder = field[ fieldName ].order || bs.extendedSearch.Lookup.SORT_DESC;
		}
	}
	this.currentSortFields = sortedFields;
	this.currentSortOrder = sortOrder;
};

/**
 * Sets config object used for search options
 *
 * @return {Object}
 */
bs.extendedSearch.ToolsPanel.prototype.getSearchOptionsConfig = function () {
	this.setSortableFields();
	this.setCurrentSortFields();

	const pageSizeConfig = bs.extendedSearch.SearchCenter.getPageSizeConfig();
	if ( this.optionStorage.getOptions().pageSize ) {
		pageSizeConfig.value = this.optionStorage.getOptions().pageSize;
	}

	return {
		pageSize: pageSizeConfig,
		sortBy: {
			value: this.currentSortFields,
			options: this.sortableFields
		},
		sortOrder: {
			// Because _score is default sort field, it needs to be sorted descending
			value: this.currentSortOrder,
			options: [
				{
					data: bs.extendedSearch.Lookup.SORT_ASC,
					label: mw.message( 'bs-extendedsearch-search-center-sort-order-asc' ).text()
				},
				{
					data: bs.extendedSearch.Lookup.SORT_DESC,
					label: mw.message( 'bs-extendedsearch-search-center-sort-order-desc' ).text()
				}
			]
		}
	};
};

/**
 * Adds and opens search options dialog
 *
 * @param {Object} dialog
 */
bs.extendedSearch.ToolsPanel.prototype.openDialog = function ( dialog ) {
	const windowManager = OO.ui.getWindowManager();
	windowManager.addWindows( [ dialog ] );
	windowManager.openWindow( dialog );
};

/**
 * Creates instance of FilterWidget and adds it to the page
 *
 * @param {Array} cfg
 * @return {bs.extendedSearch.FilterWidget}
 */
bs.extendedSearch.ToolsPanel.prototype.addFilterWidget = function ( cfg ) {
	cfg.showRemove = true;
	cfg.mobile = this.mobile;

	const filter = new bs.extendedSearch.FilterWidget( cfg );
	filter.$element.on( 'removeWidgetClick', this.onRemoveFilterWidget.bind( this ) );
	filter.$element.on( 'filterOptionsChanged', this.onChangeFilterOption.bind( this ) );

	this.appendFilter( filter, cfg.id );
	this.activeFilters.push( cfg.id );
	return filter;
};

/**
 * Handles changes to filter options
 *
 * @param {Event} e
 * @param {Object} params
 */
bs.extendedSearch.ToolsPanel.prototype.onChangeFilterOption = function ( e, params ) {
	this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

	if ( params.filterId === 'type' ) {
		params.filterId = '_type';
	}

	for ( let i = 0; i < params.options.length; i++ ) {
		const value = params.options[ i ];
		this.lookup.removeFilter( params.filterId, value.data );
	}

	if ( params.filterType === 'and' ) {
		this.lookup.addTermFilter( params.filterId, params.values );
	} else {
		this.lookup.addTermsFilter( params.filterId, params.values );
	}

	this.lookup.setFrom( 0 );
	bs.extendedSearch.SearchCenter.updateQueryHash();
};

bs.extendedSearch.ToolsPanel.prototype.onRemoveFilterWidget = function ( e, params ) {
	this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

	$( e.target ).remove();

	if ( params.filterId === 'type' ) {
		params.filterId = '_type';
	}

	this.lookup.clearFilter( params.filterId );

	this.lookup.setFrom( 0 );
	bs.extendedSearch.SearchCenter.updateQueryHash();
};

bs.extendedSearch.ToolsPanel.prototype.onAddFilter = function ( cfg ) {
	const filter = this.addFilterWidget( cfg );
	filter.showOptions();
};

/**
 * Reads in filters currently set in Lookup object
 * and adds corresponding filters with correct values to the UI
 *
 */
bs.extendedSearch.ToolsPanel.prototype.addFiltersFromLookup = function () {
	const queryFiltersWithTypes = this.lookup.getFilters();
	const filters = [];
	for ( const filterType in queryFiltersWithTypes ) {
		if ( !queryFiltersWithTypes.hasOwnProperty( filterType ) ) {
			continue;
		}
		const queryFilter = queryFiltersWithTypes[ filterType ];
		for ( let filterId in queryFilter ) {
			if ( !queryFilter.hasOwnProperty( filterId ) ) {
				continue;
			}
			const filterValues = queryFilter[ filterId ];
			if ( filterId === '_type' ) {
				filterId = 'type';
			}
			for ( let i = 0; i < this.filterData.length; i++ ) {
				const filter = this.filterData[ i ].filter;
				if ( filter.id !== filterId ) {
					continue;
				}

				if ( filterType === 'terms' ) {
					filter.filterType = 'or';
				} else if ( filterType === 'term' ) {
					filter.filterType = 'and';
				}

				filter.selectedOptions = filterValues;

				// in case selected options are not in offered options we must add them
				for ( let j = 0; j < filter.selectedOptions.length; j++ ) {
					const selectedOption = filter.selectedOptions[ j ].toString();
					let hasOption = false;
					for ( let k = 0; k < filter.options.length; k++ ) {
						if ( filter.options[ k ].data === selectedOption ) {
							hasOption = true;
							break;
						}
					}
					if ( !hasOption ) {
						filter.options.push( {
							label: selectedOption,
							data: selectedOption
						} );
					}
				}
				filters.push( filter );
			}
		}
	}
	mw.hook( 'bs.extendedSearch.ToolsPanel.addFilters' ).fire( filters, this, this.lookup );
	for ( let i = 0; i < filters.length; i++ ) {
		this.addFilterWidget( filters[ i ] );
	}
};

bs.extendedSearch.ToolsPanel.prototype.addContextPill = function () {
	const context = this.lookup.getContext();
	if ( !context || !context.showCustomPill ) {
		return;
	}

	const contextButton = new bs.extendedSearch.ContextButton( {
		rawText: context.text.replace( /<[^>]*>?/gm, '' ),
		label: new OO.ui.HtmlSnippet( context.text )
	} );
	contextButton.connect( this, {
		remove: function () {
			this.lookup.setContext( null );
			this.lookup.setFrom( 0 );
			bs.extendedSearch.SearchCenter.updateQueryHash();
		}
	} );
	this.$filtersContainer.append( contextButton.$element );
};

bs.extendedSearch.ToolsPanel.prototype.addDefaultFilters = function () {
	for ( let i = 0; i < this.defaultFilters.length; i++ ) {
		const defFilter = this.defaultFilters[ i ];
		for ( let availableFilterIdx = 0; availableFilterIdx < this.filterData.length; availableFilterIdx++ ) {
			const filter = this.filterData[ availableFilterIdx ].filter;
			if ( filter.id !== defFilter ) {
				continue;
			}
			this.addFilterWidget( filter );
		}
	}
};

bs.extendedSearch.ToolsPanel.prototype.openExportDialog = function () {
	const headers = $( '.bs-extendedsearch-result-header' );
	const pages = [];
	$.each( headers, ( k, value ) => { // eslint-disable-line no-jquery/no-each-util
		const data = $( value ).data( 'bs-traceable-page' );
		if ( data && data.dbkey ) {
			pages.push( { dbkey: data.dbkey, display: data.dbkey } );
		}
	} );

	const term = this.caller.getLookupObject().getQueryString().query;
	mw.loader.using( 'ext.bluespice.oojs.exportDialog', () => {
		const title = mw.Title.newFromText( term );
		const dialog = new bs.ui.dialog.ExportPagesDialog( {
			pages: pages,
			listName: title.getMainText()
		} );
		this.openDialog( dialog );
	} );
};
