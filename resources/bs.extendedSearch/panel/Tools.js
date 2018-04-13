( function( mw, $, bs, d, undefined ) {
	bs.extendedSearch.ToolsPanel = function( cfg ) {
		this.cfg = cfg || {};
	}

	bs.extendedSearch.ToolsPanel.prototype.init = function() {
		this.lookup = this.cfg.lookup;
		this.filterData = this.cfg.filterData;

		//Replaces "add filter" button
		$( '#bs-extendedSearch-filter-add-button' ).remove();

		var addFilterWidgetObject = new bs.extendedSearch.FilterAddWidget( { filterData: this.filterData } );
		addFilterWidgetObject.$element.on( 'widgetToAddSelected', this.onWidgetToAddSelected.bind( this ) );

		//Adds button that shows search options dialog
		this.optionsButton = new OO.ui.ButtonWidget( {
			icon: 'settings',
			label: ''
		} );

		this.setSearchOptionsConfig();

		this.optionsButton.$element.on( 'click', { options: this.searchOptionsConfig }, this.openOptionsDialog.bind( this ) );

		$( '#bs-es-tools' ).append(
			$( '<div>' ).attr( 'id', 'bs-es-tools-filters' ).append( addFilterWidgetObject.$element ),
			$( '<div>' ).attr( 'id', 'bs-es-tools-tools' ).append( this.optionsButton.$element )
		);

		this.addFiltersFromLookup();
	}

	/**
	 * Actually adds FilterWidget element to DOM
	 *
	 * @param {bs.extendedSearch.FilterWidget} filter
	 * @param {String} id
	 */
	bs.extendedSearch.ToolsPanel.prototype.appendFilter = function( filter, id ) {
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
	bs.extendedSearch.ToolsPanel.prototype.applyValuesFromOptionsDialog = function( values ) {
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
	 * Converts simple array of sortable fields
	 * to array of valid config objects
	 */
	bs.extendedSearch.ToolsPanel.prototype.setSortableFields = 	function() {
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
	bs.extendedSearch.ToolsPanel.prototype.setCurrentSortFields = function() {
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

	/**
	 * Sets config object used for search options
	 */
	bs.extendedSearch.ToolsPanel.prototype.setSearchOptionsConfig = function() {
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
	 * Adds and opens search options dialog
	 */
	bs.extendedSearch.ToolsPanel.prototype.openOptionsDialog = function( e ) {
		var windowManager = OO.ui.getWindowManager();

		var cfg = e.data || {};

		var dialog = new bs.extendedSearch.OptionsDialog( cfg, this );

		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog );
	}

	/**
	 * Creates instance of FilterWidget and adds it to the page
	 *
	 * @param {Array} cfg
	 * @return {bs.extendedSearch.FilterWidget}
	 */
	bs.extendedSearch.ToolsPanel.prototype.addFilterWidget = function( cfg ) {
		cfg.showRemove = true;

		var filter = new bs.extendedSearch.FilterWidget( cfg );
		filter.$element.on( 'removeWidgetClick', this.onRemoveFilterWidget.bind( this ) );
		filter.$element.on( 'filterOptionsChanged', this.onChangeFilterOption.bind( this ) );

		this.appendFilter(
			filter,
			cfg.id
		);

		return filter;
	}

	/**
	 * Handles changes to filter options
	 */
	bs.extendedSearch.ToolsPanel.prototype.onChangeFilterOption = function ( e, params ) {
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

	bs.extendedSearch.ToolsPanel.prototype.onRemoveFilterWidget = function ( e, params ) {
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

	bs.extendedSearch.ToolsPanel.prototype.onWidgetToAddSelected = function( e, data ) {
		var cfg = data.cfg;
		var filter = this.addFilterWidget( cfg );
		data.window.close();
		filter.showOptions();
	}

	/**
	 * Reads in filters currently set in Lookup object
	 * and adds corresponding filters with correct values to the UI
	 *
	 */
	bs.extendedSearch.ToolsPanel.prototype.addFiltersFromLookup = function() {
		var queryFilters = this.lookup.getFilters();
		for( idx in queryFilters ) {
			var queryFilter = queryFilters[idx];
			for( filterId in queryFilter ) {
				for( availableFilterIdx in this.filterData ) {
					var filter = this.filterData[availableFilterIdx].filter;
					if( filter.id !== filterId ) {
						continue;
					}
					filter.selectedOptions = queryFilter[filterId];
					//in case selected options are not in offered options we must add them
					for( optionIdx in filter.selectedOptions ) {
						var selectedOption = filter.selectedOptions[optionIdx];
						if( filter.options.indexOf( selectedOption ) == -1 ) {
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

		//Adds special filter "type" (based on source types)
		if( this.lookup.getTypes().length > 0 ) {
			for( idx in this.filterData ){
				var filter = this.filterData[idx].filter;
				if( filter.id === 'type' ) {
					filter.selectedOptions = this.lookup.getTypes();
					this.addFilterWidget( filter );
				}
			}
		}
	}

} )( mediaWiki, jQuery, blueSpice, document );


