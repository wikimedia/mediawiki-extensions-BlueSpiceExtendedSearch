( function( mw, $, bs, d, undefined ) {
	bs.extendedSearch.ToolsPanel = function( cfg ) {
		this.cfg = cfg || {};
	}

	bs.extendedSearch.ToolsPanel.prototype.init = function() {

		this.lookup = this.cfg.lookup;
		this.filterData = this.cfg.filterData;
		this.caller = this.cfg.caller;
		this.mobile = this.cfg.mobile || false;
		this.hitCounter = this.cfg.hitCounter;
		this.pageCreateData = this.cfg.pageCreateData;

		this.defaultFilters = this.cfg.defaultFilters || [];

		this.$element = $( '#bs-es-tools' );

		// Replaces "add filter" button
		$( '#bs-extendedsearch-filter-add-button' ).remove();

		this.$toolsContainer = $( '<div>' ).attr( 'id', 'bs-es-tools-tools' );
		if( $.isEmptyObject( this.pageCreateData ) === false ) {
			var createPageButton = new OO.ui.ButtonWidget( {
				framed: false,
				label: '',
				href: this.pageCreateData.url
			} );
			createPageButton.$element.addClass( 'bs-extendedsearch-create-page-button tools-button' );
			createPageButton.$element.attr(
				'title',
				mw.message( 'bs-extendedsearch-search-center-create-page-link', this.pageCreateData.title ).text()
			);
			createPageButton.$element.on( 'click', { url: this.pageCreateData.url }, function( e ) {
				window.location.href = e.data.url;
			} );
			this.$toolsContainer.append( createPageButton.$element );
		}

		var addFilterWidget = new bs.extendedSearch.FilterAddWidget( { filterData: this.filterData } );
		addFilterWidget.$element.on( 'widgetToAddSelected', this.onWidgetToAddSelected.bind( this ) );

		//Adds button that shows search options dialog
		this.optionsButton = new OO.ui.ButtonWidget( {
			framed: false,
			label: ''
		} );
		this.optionsButton.$element.addClass( 'bs-extendedsearch-settings-button tools-button' );
		this.optionsButton.$element.attr( 'title', mw.message( "bs-extendedsearch-options-button-label" ).text() );
		this.setSearchOptionsConfig();

		this.optionsButton.$element.on( 'click', { options: this.searchOptionsConfig }, this.openOptionsDialog.bind( this ) );

		this.$filtersContainer = $( '<div>' ).attr( 'id', 'bs-es-tools-filters' );
		this.$toolsContainer.append(
			addFilterWidget.$element,
			this.optionsButton.$element
		);

		if ( mw.config.get( 'bsgESUserCanExport' ) ) {
			this.exportButton = new OO.ui.ButtonWidget( {
				framed: false,
				label: ''
			} );
			this.exportButton.$element.addClass( 'bs-extendedsearch-export-button tools-button' );
			this.exportButton.$element.attr( 'title', mw.message( "bs-extendedsearch-export-button-label" ).text() );
			this.exportButton.$element.on( 'click', this.showExportSearchDialog.bind( this ) );
			this.$toolsContainer.append( this.exportButton.$element );
		}

		this.$element.append(
			this.hitCounter.$element,
			this.$toolsContainer,
			this.$filtersContainer
		);
		this.$element.addClass( 'bs-es-tools' );

		if( this.mobile ) {
			this.$element.addClass( 'mobile' );
		}

		this.addFiltersFromLookup();
		this.addDefaultFilters();
	};

	/**
	 * Actually adds FilterWidget element to DOM
	 *
	 * @param {bs.extendedSearch.FilterWidget} filter
	 * @param {String} id
	 */
	bs.extendedSearch.ToolsPanel.prototype.appendFilter = function( filter, id ) {
		var existingFilter = $( '#bs-extendedsearch-filter-' + id );
		if( existingFilter.length > 0 ) {
			return;
		}
		this.$filtersContainer.append( filter.$element );
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

		for( var i = 0; i < this.currentSortFields.length; i++ ) {
			var sortedField = this.currentSortFields[i];
			if( sortBy.indexOf( sortedField ) === -1 ) {
				this.lookup.removeSort( sortedField );
			}
		}

		for( var i = 0; i < sortBy.length; i++ ) {
			this.lookup.addSort( sortBy[i], sortOrder );
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
	}

	/**
	 * Gets current sort fields and order from Lookup object
	 * and converts it to simple array usable in dialog
	 */
	bs.extendedSearch.ToolsPanel.prototype.setCurrentSortFields = function() {
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
				//Because _score is default sort field, it needs to be sorted descending
				value: this.currentSortOrder || bs.extendedSearch.Lookup.SORT_DESC,
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
		cfg.mobile = this.mobile;

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
			params.filterId = bs.extendedSearch.Lookup.TYPE_FIELD_NAME;
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
			params.filterId = bs.extendedSearch.Lookup.TYPE_FIELD_NAME;
		}

		this.lookup.clearFilter( params.filterId );

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
				if( filterId == bs.extendedSearch.Lookup.TYPE_FIELD_NAME ) {
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
	}

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

	bs.extendedSearch.ToolsPanel.prototype.showExportSearchDialog = function() {
		var headers = $( '.bs-extendedsearch-result-header' );
		var pages = [];
		$.each( headers, function( k, value ) {
			var title = $( value ).data( 'bs-title' );
			if( title ) {
				pages.push( title );
			}
		} );

		let term = this.caller.getLookupObject().getQueryString().query;
		if ( this.dialog ) {
			// Destroy previous instance to prevent double input rendering
			this.dialog.destroy();
		}
		this.dialog = Ext.create( 'BS.dialog.PageExport', {
			pages: pages,
			defaultName: term
		});
		this.dialog.show();
	}

} )( mediaWiki, jQuery, blueSpice, document );
