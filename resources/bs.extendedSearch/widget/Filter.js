( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.FilterWidget = function( cfg ) {
		cfg = cfg || {};

		this.id = cfg.id;
		this.options = cfg.options || [];
		this.selectedOptions = cfg.selectedOptions || [];

		this.dirty = false;
		this.isOpen = false;

		this.emptyLabel = cfg.label;
		this.valueLabel = cfg.valueLabel;

		cfg.popup = {
			$content: this.getPopupContentWidgetElement(),
			align: 'forwards',
			padded: true,
			autoClose: true
		}

		bs.extendedSearch.FilterWidget.parent.call( this, cfg );

		OO.ui.mixin.ButtonElement.call( this, cfg );
		OO.ui.mixin.LabelElement.call( this, cfg );
		OO.ui.mixin.PopupElement.call( this, cfg );
		bs.extendedSearch.mixin.FilterRemoveButton.call( this, cfg );

		this.$button
			.addClass( 'bs-extendedsearch-filter-button-button' )
			.append( this.$label );

		this.connect( this, { click: 'onShowOptions' } );

		this.popup.$element
			.addClass( 'oo-ui-popupButtonWidget-popup' )
			.toggleClass( 'oo-ui-popupButtonWidget-framed-popup', this.isFramed() )
			.toggleClass( 'oo-ui-popupButtonWidget-frameless-popup', !this.isFramed() );

		this.$element
			.attr( 'id', 'bs-extendedSearch-filter-' + cfg.id )
			.attr( 'aria-haspopup', 'true' )
			.addClass( 'oo-ui-popupButtonWidget bs-extendedsearch-filter-button-widget' )
			.append( this.$button, this.$removeButton, this.popup.$element );

		//PRESELECTED OPTIONS
		if( this.selectedOptions.length > 0 ) {
			this.setFilterLabel();
		}
	}

	OO.inheritClass( bs.extendedSearch.FilterWidget, OO.ui.Widget );

	OO.mixinClass( bs.extendedSearch.FilterWidget, OO.ui.mixin.ButtonElement );
	OO.mixinClass( bs.extendedSearch.FilterWidget, OO.ui.mixin.LabelElement );
	OO.mixinClass( bs.extendedSearch.FilterWidget, OO.ui.mixin.PopupElement );
	OO.mixinClass( bs.extendedSearch.FilterWidget, bs.extendedSearch.mixin.FilterRemoveButton );

	bs.extendedSearch.FilterWidget.prototype.onShowOptions = function()  {
		this.popup.toggle();
		this.isOpen = !this.isOpen;

		if( this.dirty === false ) {
			return;
		}
		this.applyFilter();
	};

	bs.extendedSearch.FilterWidget.prototype.showOptions = function() {
		if( this.isOpen === false ) {
			this.popup.toggle();
			this.isOpen = true;
		}
	}
	bs.extendedSearch.FilterWidget.prototype.applyFilter = function() {
		this.$element.trigger( 'filterOptionsChanged', {
			filterId: this.id,
			values: this.selectedOptions,
			options: this.options
		} );

		this.dirty = false;
	}

	bs.extendedSearch.FilterWidget.prototype.getPopupContentWidgetElement = function()  {
		if( this.options.length === 0 ){
			return new OO.ui.LabelWidget( {
				label: mw.message( 'bs-extendedsearch-search-center-filter-no-options-label' ).plain()
			} ).$element;
		}

		this.$optionsContainer = $( '<div>' );

		this.filterBox = new OO.ui.SearchInputWidget();
		this.filterBox.on( 'change', function () {
			this.onOptionsFilter();
		}.bind( this ) );

		this.applyFilterButton = new OO.ui.ButtonWidget( {
			label: 'OK',
			flags: 'primary'
		} );
		this.applyFilterButton.on( 'click', function() {
			this.onApplyFilterButton();
		}.bind( this ) );

		this.actions = new OO.ui.ActionFieldLayout( this.filterBox, this.applyFilterButton, { align: 'inline' } );

		this.$optionsContainer.append( this.actions.$element );

		this.optionsCheckboxWidgetID = 'bs-extendedSearch-filter-options-checkbox-widget';
		this.addCheckboxWidget( this.options );

		return this.$optionsContainer;
	};

	bs.extendedSearch.FilterWidget.prototype.addCheckboxWidget = function( options ) {
		this.optionsCheckboxWidget = new bs.extendedSearch.FilterOptionsCheckboxWidget( {
			value: this.selectedOptions,
			options: options
		} );

		this.optionsCheckboxWidget.$element.attr( 'id', this.optionsCheckboxWidgetID );

		this.optionsCheckboxWidget.checkboxMultiselectWidget.on( 'change', function () {
			this.onOptionsChange( this.optionsCheckboxWidget.checkboxMultiselectWidget.findSelectedItemsData() );
		}.bind( this ) );

		this.$optionsContainer.append( this.optionsCheckboxWidget.$element );
	}

	bs.extendedSearch.FilterWidget.prototype.onOptionsFilter = function() {
		var searchTerm = this.filterBox.value;
		var filteredOptions = [];
		for( idx in this.options ) {
			var option = this.options[idx];
			if( this.selectedOptions.indexOf( option.data ) !== -1 ) {
				filteredOptions.push( option );
				continue;
			}

			if( option.data.toLowerCase().includes( searchTerm.toLowerCase() ) ) {
				filteredOptions.push( option );
			}
		}
		this.$optionsContainer.children( '#' + this.optionsCheckboxWidgetID ).remove();
		this.addCheckboxWidget( filteredOptions );
	}

	bs.extendedSearch.FilterWidget.prototype.onApplyFilterButton = function() {
		this.applyFilter();
	}

	bs.extendedSearch.FilterWidget.prototype.setFilterLabel = function() {
		if( this.selectedOptions.length == 0 ) {
			this.setLabel( this.emptyLabel );
		} else {
			this.setLabel( this.valueLabel + this.selectedOptions.join( ', ' ) );
		}
	}

	bs.extendedSearch.FilterWidget.prototype.onOptionsChange = function( values )  {
		this.selectedOptions = values;
		this.setFilterLabel();

		this.dirty = true;
	};

	bs.extendedSearch.FilterAddWidget = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterAddWidget.parent.call( this, cfg );

		OO.ui.mixin.ButtonElement.call( this, cfg );

		this.$button
			.addClass( 'bs-extendedsearch-filter-add-widget-button' )
			.append( this.$indicator )
			.on( 'click', { cfg: cfg, parent: this }, this.openAddWidgetDialog );

		this.$element
			.attr( 'id', 'bs-extendedSearch-filter-add-button' )
			.addClass( 'bs-extendedsearch-filter-add-widget' )
			.append( this.$button );
	}

	OO.inheritClass( bs.extendedSearch.FilterAddWidget, OO.ui.Widget );

	OO.mixinClass( bs.extendedSearch.FilterAddWidget, OO.ui.mixin.ButtonElement );

	bs.extendedSearch.FilterAddWidget.prototype.openAddWidgetDialog = function( e ) {
		var windowManager = OO.ui.getWindowManager();

		var cfg = e.data.cfg || {};
		cfg.size = 'small';
		cfg.parentButton = e.data.parent.$element;

		var dialog = new bs.extendedSearch.FilterAddDialog( cfg );

		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog );
	}

	bs.extendedSearch.FilterOptionsCheckboxWidget = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterOptionsCheckboxWidget.parent.call( this, cfg );

		this.$element.addClass( 'bs-extendedsearch-filter-group' );
	}

	OO.inheritClass( bs.extendedSearch.FilterOptionsCheckboxWidget, OO.ui.CheckboxMultiselectInputWidget );

	bs.extendedSearch.FilterOptionsCheckboxWidget.prototype.setOptions = function( options ) {
		var widget = this;

		// Rebuild the checkboxMultiselectWidget menu
		this.checkboxMultiselectWidget
			.clearItems()
			.addItems( options.map( function ( opt ) {
				var optValue, item, optDisabled;
				optValue =
					OO.ui.CheckboxMultiselectInputWidget.parent.prototype.cleanUpValue.call( widget, opt.data );
				optDisabled = opt.disabled !== undefined ? opt.disabled : false;
				item = new OO.ui.CheckboxMultioptionWidget( {
					data: optValue,
					label: opt.label !== undefined ? opt.label : optValue,
					disabled: optDisabled
				} );
				// Set the 'name' and 'value' for form submission
				item.checkbox.$input.attr( 'name', widget.inputName );
				item.checkbox.setValue( optValue );
				item.$element
					.append(
						$('<p>')
							.html( opt.count )
							.addClass( 'bs-extendedsearch-filter-option-count' )
					)
					.addClass( 'bs-extendedsearch-filter-option' );
				return item;
			} ) );

		// Re-set the value, checking the checkboxes as needed.
		// This will also get rid of any stale options that we just removed.
		this.setValue( this.getValue() );

		return this;
	}
} )( mediaWiki, jQuery, blueSpice, document );