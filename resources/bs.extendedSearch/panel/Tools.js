( function( mw, $, bs, d, undefined ) {
	/**
	 * Reads in filters currently set in Lookup object
	 * and adds corresponding filters with correct values to the UI
	 *
	 * @param {array} availableFilters
	 */
	function addFiltersFromLookup( availableFilters ) {
		var lookup = bs.extendedSearch.SearchCenter.getLookupObject();
		var queryFilters = lookup.getFilters();
		for( idx in queryFilters ) {
			var queryFilter = queryFilters[idx];
			for( filterId in queryFilter ) {
				for( availableFilterIdx in availableFilters ) {
					var filter = availableFilters[availableFilterIdx].filter;
					if( filter.id !== filterId ) {
						continue;
					}
					filter.selectedOptions = queryFilter[filterId];

					addFilterWidget( filter );
				}
			}
		}

		//Adds special filter "type" (based on source types)
		if( lookup.getTypes().length > 0 ) {
			for( idx in availableFilters ){
				var filter = availableFilters[idx].filter;
				if( filter.id === 'type' ) {
					filter.selectedOptions = lookup.getTypes();
					addFilterWidget( filter );
				}
			}
		}
	}

	function onWidgetToAddSelected( e, data ) {
		var cfg = data.cfg;
		var filter = addFilterWidget( cfg );
		data.window.close();
		filter.showOptions();
	}

	function onRemoveFilterWidget( e, params ) {
		this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

		var filterId = $( e.target ).attr( 'id' );
		$( '#bs-es-tools-filters' ).children().remove( '#' + filterId );

		for( idx in params.options ) {
			if( params.filterId == 'type' ) {
				this.lookup.clearTypes();
				continue;
			}
			var value = params.options[idx];
			this.lookup.removeFilter( params.filterId, value.data );
		}

		this.lookup.setFrom( 0 );
		bs.extendedSearch.SearchCenter.updateQueryHash();
	}

	/**
	 * Handles changes to filter options
	 */
	function onChangeFilterOption( e, params ) {
		this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

		if( params.filterId == 'type' ) {
			this.lookup.clearTypes();
			this.lookup.setTypes( params.values );
		} else {
			for( idx in params.options ) {
				var value = params.options[idx];
				this.lookup.removeFilter( params.filterId, value.data );
			}

			for( idx in params.values ) {
				var value = params.values[idx];
				this.lookup.addFilter( params.filterId, value );
			}
		}

		this.lookup.setFrom( 0 );
		bs.extendedSearch.SearchCenter.updateQueryHash();
	}

	/**
	 * Creates instance of FilterWidget and adds it to the page
	 *
	 * @param {Array} cfg
	 * @return {bs.extendedSearch.FilterWidget}
	 */
	function addFilterWidget( cfg ) {
		cfg.showRemove = true;

		var filter = new bs.extendedSearch.FilterWidget( cfg );
		filter.$element.on( 'removeWidgetClick', onRemoveFilterWidget );
		filter.$element.on( 'filterOptionsChanged', onChangeFilterOption );

		bs.extendedSearch.ToolsPanel.appendFilter(
			filter,
			cfg.id
		);

		return filter;
	}

	function _init() {
		this.lookup = bs.extendedSearch.SearchCenter.getLookupObject();

		//Replaces "add filter" button
		$( '#bs-extendedSearch-filter-add-button' ).remove();

		var addFilterWidgetObject = new bs.extendedSearch.FilterAddWidget( { filterData: this.filterData } );
		addFilterWidgetObject.$element.on( 'widgetToAddSelected', onWidgetToAddSelected );

		//Adds button that shows search options dialog
		this.optionsButton = new OO.ui.ButtonWidget( {
			icon: 'settings',
			label: ''
		} );

		this.setSearchOptionsConfig();

		this.optionsButton.$element.on( 'click', { options: this.searchOptionsConfig }, openOptionsDialog );

		$( '#bs-es-tools' ).append(
			$( '<div>' ).attr( 'id', 'bs-es-tools-filters' ).append( addFilterWidgetObject.$element ),
			$( '<div>' ).attr( 'id', 'bs-es-tools-tools' ).append( this.optionsButton.$element )
		);

		addFiltersFromLookup( this.filterData );
	}

	function _setFilterData( filters ) {
		this.filterData = filters;
	}

	/**
	 * Adds and opens search options dialog
	 */
	function openOptionsDialog( e ) {
		var windowManager = OO.ui.getWindowManager();

		var cfg = e.data || {};

		var dialog = new bs.extendedSearch.OptionsDialog( cfg );

		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog );
	}

	/**
	 * Actually adds FilterWidget element to DOM
	 *
	 * @param {bs.extendedSearch.FilterWidget} filter
	 * @param {String} id
	 */
	function _appendFilter( filter, id ) {
		var addFilterButton = $( '#bs-extendedSearch-filter-add-button' );
		var existingFilter = $( '#bs-extendedSearch-filter-' + id );
		if( existingFilter.length > 0 ) {
			return;
		}
		filter.$element.insertBefore( addFilterButton );
	}

	/**
	 * Called from bs.extendedSearch.OptionsDialog.
	 * Reads in and applies valus from dialog to the Lookup object
	 *
	 * @param {Array} values
	 */
	function _applyValuesFromOptionsDialog( values ) {
		var size = values.pageSize || 0;
		this.lookup.setSize( size );

		var sortBy = values.sortBy || [];
		var sortOrder = values.sortOrder || bs.extendedSearch.Lookup.SORT_ASC;

		for( idx in this.currentSortFields ) {
			var sortedField = this.currentSortFields[idx];
			if( sortBy.indexOf( sortedField ) == -1 ) {
				this.lookup.removeSort( sortedField );
			}
		}

		for( idx in sortBy ) {
			this.lookup.addSort( sortBy[idx], sortOrder );
		}

		bs.extendedSearch.SearchCenter.updateQueryHash();
	}

	/**
	 * Sets config object used for search options
	 */
	function _setSearchOptionsConfig() {
		this.setSortableFields();
		this.setCurrentSortFields();

		this.searchOptionsConfig = {
			pageSize: bs.extendedSearch.SearchCenter.getPageSizeConfig(),
			sortBy: {
				value: this.currentSortFields,
				options: this.sortableFields
			},
			sortOrder: {
				value: this.currentSortOrder || bs.extendedSearch.Lookup.SORT_ASC,
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
	}

	/**
	 * Converts simple array of sortable fields
	 * to array of valid config objects
	 */
	function _setSortableFields() {
		var fields = mw.config.get( 'bsgESSortableFields' );
		this.sortableFields = [];
		for( fieldIdx in fields ) {
			var field = fields[fieldIdx];
			this.sortableFields.push(
				{
					data: field,
					label: field.charAt(0).toUpperCase() + field.slice(1)
				}
			);
		}
	}

	/**
	 * Gets current sort fields and order from Lookup object
	 * and converts it to simple array usable in dialog
	 */
	function _setCurrentSortFields() {
		var sortedFields = [];
		var sortOrder = '';
		for( sortIdx in this.lookup.getSort() ) {
			var field = this.lookup.getSort()[sortIdx];
			for( fieldName in field ) {
				sortedFields.push( fieldName );
				sortOrder = field[fieldName].order;
			}
		}
		this.currentSortFields = sortedFields;
		this.currentSortOrder = sortOrder;
	}

	bs.extendedSearch.ToolsPanel = {
		setFilterData: _setFilterData,
		init: _init,
		appendFilter: _appendFilter,
		setSortableFields: _setSortableFields,
		applyValuesFromOptionsDialog: _applyValuesFromOptionsDialog,
		setCurrentSortFields: _setCurrentSortFields,
		setSearchOptionsConfig: _setSearchOptionsConfig
	};
} )( mediaWiki, jQuery, blueSpice, document );


