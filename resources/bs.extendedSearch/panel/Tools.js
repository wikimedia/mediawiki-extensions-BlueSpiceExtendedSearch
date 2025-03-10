bs.extendedSearch.ToolsPanel = function( cfg ) {
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

	if( this.mobile ) {
		this.$element.addClass( 'mobile' );
	}
};

OO.inheritClass( bs.extendedSearch.ToolsPanel, OO.ui.PanelLayout );

bs.extendedSearch.ToolsPanel.prototype.init = function() {
	this.toolsContainer = new OO.ui.HorizontalLayout( {
		classes: [ 'bs-es-tools-tools' ]
	} );
	this.$filtersContainer = $( '<div>' ).attr( 'id', 'bs-es-tools-filters' );

	this.addFiltersFromLookup();
	this.addDefaultFilters();

	const addFilterWidget = new bs.extendedSearch.FilterAddWidget( {
		filterData: this.filterData,
		activeFilters: this.activeFilters,
	} );
	addFilterWidget.connect( this, {
		addFilter: 'onAddFilter'
	} );



	const menuButton = new OO.ui.ButtonMenuSelectWidget( {
		icon: 'menu',
		title: mw.message( "bs-extendedsearch-options-button-label" ).text(),
		menu: {
			horizontalPosition: 'end',
			items: [
				new OO.ui.MenuOptionWidget( {
					data: 'options',
					icon: 'settings',
					label: mw.message( "bs-extendedsearch-options-button-label" ).text()
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
		select: function( item ) {
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
		this.hitCounter.$element,
		$( '<div>' ).addClass( 'bs-es-tools-tools-container' ).append(
			this.$filtersContainer,
			this.toolsContainer.$element
		)
	);
};

/**
 * Actually adds FilterWidget element to DOM
 *
 * @param {bs.extendedSearch.FilterWidget} filter
 * @param {String} id
 */
bs.extendedSearch.ToolsPanel.prototype.appendFilter = function( filter, id ) {
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
bs.extendedSearch.ToolsPanel.prototype.updateSearchOptions = function( values ) {
	this.optionStorage.setOptions( values );
	this.applySearchOptions();
	bs.extendedSearch.SearchCenter.updateQueryHash();
};

bs.extendedSearch.ToolsPanel.prototype.applySearchOptions = function( lookup ) {
	lookup = lookup || this.lookup;
	const values = this.optionStorage.getOptions();
	var size = values.pageSize || 0;
	lookup.setSize( size );

	var sortBy = values.sortBy || [];
	var sortOrder = values.sortOrder || bs.extendedSearch.Lookup.SORT_ASC;

	let i;
	for( i = 0; i < this.currentSortFields.length; i++ ) {
		var sortedField = this.currentSortFields[i];
		if( sortBy.indexOf( sortedField ) === -1 ) {
			lookup.removeSort( sortedField );
		}
	}

	for( i = 0; i < sortBy.length; i++ ) {
		lookup.addSort( sortBy[i], sortOrder );
	}
};

/**
 * Converts simple array of sortable fields
 * to array of valid config objects
 */
bs.extendedSearch.ToolsPanel.prototype.setSortableFields = 	function() {
	var fields = mw.config.get( 'bsgESSortableFields' );
	this.sortableFields = [];
	for( var i = 0; i < fields.length; i++ ) {
		var field = fields[i];

		var label = field.charAt(0).toUpperCase() + field.slice(1);
		if( mw.message( 'bs-extendedsearch-searchcenter-sort-field-' + field ).exists() ) {
			label = mw.message( 'bs-extendedsearch-searchcenter-sort-field-' + field ).plain();
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
bs.extendedSearch.ToolsPanel.prototype.setCurrentSortFields = function() {
	const optionsFromStorage = this.optionStorage.getOptions();
	if ( optionsFromStorage.sortBy ) {
		this.currentSortFields = optionsFromStorage.sortBy;
		this.currentSortOrder = optionsFromStorage.sortOrder || bs.extendedSearch.Lookup.SORT_DESC;
		return;
	}
	var sortedFields = [];
	var sortOrder = '';
	var sort = this.lookup.getSort();
	for( var i = 0; i < sort.length; i++ ) {
		var field = sort[i];
		for( var fieldName in field ) {
			if ( !field.hasOwnProperty( fieldName ) ) {
				continue;
			}
			sortedFields.push( fieldName );
			sortOrder = field[fieldName].order || bs.extendedSearch.Lookup.SORT_DESC;
		}
	}
	this.currentSortFields = sortedFields;
	this.currentSortOrder = sortOrder;
};

/**
 * Sets config object used for search options
 */
bs.extendedSearch.ToolsPanel.prototype.getSearchOptionsConfig = function() {
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
			//Because _score is default sort field, it needs to be sorted descending
			value: this.currentSortOrder,
			options: [
				{
					data: bs.extendedSearch.Lookup.SORT_ASC,
					label: mw.message( 'bs-extendedsearch-search-center-sort-order-asc' ).plain()
				},
				{
					data: bs.extendedSearch.Lookup.SORT_DESC,
					label: mw.message( 'bs-extendedsearch-search-center-sort-order-desc' ).plain()
				}
			]
		}
	};
};

/**
 * Adds and opens search options dialog
 */
bs.extendedSearch.ToolsPanel.prototype.openDialog = function( dialog ) {
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
bs.extendedSearch.ToolsPanel.prototype.addFilterWidget = function( cfg ) {
	cfg.showRemove = true;
	cfg.mobile = this.mobile;

	var filter = new bs.extendedSearch.FilterWidget( cfg );
	filter.$element.on( 'removeWidgetClick', this.onRemoveFilterWidget.bind( this ) );
	filter.$element.on( 'filterOptionsChanged', this.onChangeFilterOption.bind( this ) );

	this.appendFilter( filter, cfg.id) ;
	this.activeFilters.push( cfg.id );
	return filter;
};

/**
 * Handles changes to filter options
 */
bs.extendedSearch.ToolsPanel.prototype.onChangeFilterOption = function ( e, params ) {
	this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

	if( params.filterId == 'type' ) {
		params.filterId = '_type';
	}

	for( var i = 0; i < params.options.length; i++ ) {
		var value = params.options[i];
		this.lookup.removeFilter( params.filterId, value.data );
	}

	if( params.filterType == 'and' ) {
		this.lookup.addTermFilter( params.filterId, params.values );
	} else {
		this.lookup.addTermsFilter( params.filterId, params.values );
	}

	this.lookup.setFrom( 0 );
	bs.extendedSearch.SearchCenter.updateQueryHash();
}

bs.extendedSearch.ToolsPanel.prototype.onRemoveFilterWidget = function ( e, params ) {
	this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

	$( e.target ).remove();

	if( params.filterId == 'type' ) {
		params.filterId = '_type';
	}

	this.lookup.clearFilter( params.filterId );

	this.lookup.setFrom( 0 );
	bs.extendedSearch.SearchCenter.updateQueryHash();
};

bs.extendedSearch.ToolsPanel.prototype.onAddFilter = function( cfg ) {
	var filter = this.addFilterWidget( cfg );
	filter.showOptions();
};

/**
 * Reads in filters currently set in Lookup object
 * and adds corresponding filters with correct values to the UI
 *
 */
bs.extendedSearch.ToolsPanel.prototype.addFiltersFromLookup = function() {
	var queryFiltersWithTypes = this.lookup.getFilters();
	for( var filterType in queryFiltersWithTypes ) {
		if ( !queryFiltersWithTypes.hasOwnProperty( filterType ) ) {
			continue;
		}
		var queryFilter = queryFiltersWithTypes[filterType];
		for( var filterId in queryFilter ) {
			if ( !queryFilter.hasOwnProperty( filterId ) ) {
				continue;
			}
			var filterValues = queryFilter[filterId];
			if( filterId === '_type' ) {
				filterId = 'type';
			}
			for( var i = 0; i < this.filterData.length; i++ ) {
				var filter = this.filterData[i].filter;
				if( filter.id !== filterId ) {
					continue;
				}

				if( filterType === 'terms' ) {
					filter.filterType = 'or';
				} else if ( filterType === 'term' ) {
					filter.filterType = 'and';
				}

				var selectedOptions = filterValues;
				filter.selectedOptions = selectedOptions;

				// in case selected options are not in offered options we must add them
				for( var j = 0; j < filter.selectedOptions.length; j++ ) {
					var selectedOption = filter.selectedOptions[j];
					var hasOption = false;
					for( var k = 0; k < filter.options.length; k++ ) {
						if( filter.options[k].data === selectedOption ) {
							hasOption = true;
							break;
						}
					}
					if( !hasOption ) {
						filter.options.push( {
							label: selectedOption,
							data: selectedOption
						} );
					}
				}

				this.addFilterWidget( filter );
			}
		}
	}
};

bs.extendedSearch.ToolsPanel.prototype.addDefaultFilters = function() {
	for( var i = 0; i < this.defaultFilters.length; i++ ) {
		var defFilter = this.defaultFilters[i];
		for( var availableFilterIdx = 0; availableFilterIdx < this.filterData.length; availableFilterIdx++ ) {
			var filter = this.filterData[availableFilterIdx].filter;
			if( filter.id !== defFilter ) {
				continue;
			}
			this.addFilterWidget( filter );
		}
	}
};

bs.extendedSearch.ToolsPanel.prototype.openExportDialog = function() {
	var headers = $( '.bs-extendedsearch-result-header' );
	var pages = [];
	$.each( headers, function( k, value ) {
		var data = $( value ).data( 'bs-traceable-page' );
		if( data && data.dbkey ) {
			pages.push( { dbkey: data.dbkey, display: data.dbkey } );
		}
	} );

	let term = this.caller.getLookupObject().getQueryString().query;
	mw.loader.using( 'ext.bluespice.oojs.exportDialog', function() {
		var title = mw.Title.newFromText( term );
		const dialog = new bs.ui.dialog.ExportPagesDialog( {
			pages: pages,
			listName: title.getMainText()
		} );
		this.openDialog( dialog );
	}.bind( this ) );
};