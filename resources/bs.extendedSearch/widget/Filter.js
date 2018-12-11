( function( mw, $, bs, d, undefined ){
	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.FilterWidget = function( cfg ) {
		cfg = cfg || {};

		this.id = cfg.id;
		this.options = cfg.options || [];
		this.selectedOptions = cfg.selectedOptions || [];
		this.isANDEnabled = cfg.isANDEnabled == 1 ? true : false;
		this.multiSelect = cfg.multiSelect == 1 ? true : false;
		this.filterType = cfg.filterType || "or";
		this.mobile = cfg.mobile || false;

		this.dirty = false;
		this.isOpen = false;

		this.emptyLabel = cfg.label;
		this.valueLabel = cfg.valueLabel;
		this.hasHiddenLabelKey = cfg.hasHiddenLabelKey;

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
			.attr( 'id', 'bs-extendedsearch-filter-' + cfg.id )
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
		if( this.selectedOptions.length == 0 ) {
			return this.removeFilter();
		}

		this.$element.trigger( 'filterOptionsChanged', {
			filterId: this.id,
			filterType: this.filterType,
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

		var layoutItems = [];
		if( this.isANDEnabled ) {
			this.andOrSwitch = new bs.extendedSearch.FilterAndOrSwitch({
				orLabel: mw.message( 'bs-extendedsearch-searchcenter-filter-or-label' ).plain(),
				andLabel: mw.message( 'bs-extendedsearch-searchcenter-filter-and-label' ).plain(),
				selected: this.filterType
			});
			this.andOrSwitch.on( 'choose', function(e) {
				this.filterType = e.data;
			}.bind( this ) );
			layoutItems.push( this.andOrSwitch );
		}

		layoutItems.push( this.applyFilterButton );

		this.actions = new OO.ui.ActionFieldLayout( this.filterBox,
			new OO.ui.HorizontalLayout( {
				items: layoutItems,
				classes: [ 'bs-extendedsearch-filter-horizontal-layout' ]
			}), { align: 'inline' } );

		this.$optionsContainer.append( this.actions.$element );

		this.addCheckboxWidget( this.options );

		return this.$optionsContainer;
	};

	bs.extendedSearch.FilterWidget.prototype.addCheckboxWidget = function( options ) {
		this.optionsCheckboxWidget = new bs.extendedSearch.FilterOptionsCheckboxWidget( {
			value: this.selectedOptions,
			options: options
		} );

		this.optionsCheckboxWidget.$element.addClass( 'bs-extendedsearch-filter-options-checkbox-widget' );

		this.optionsCheckboxWidget.checkboxMultiselectWidget.on( 'change', function () {
			this.onOptionsChange( this.selectedOptions, this.optionsCheckboxWidget.checkboxMultiselectWidget.findSelectedItemsData() );
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
		var label = '';
		if( this.selectedOptions.length == 0 ) {
			label = this.emptyLabel;
		} else if( this.mobile ) {
			var count = this.selectedOptions.length;
			label = this.valueLabel + mw.message( 'bs-extendedsearch-filter-label-count-only', count ).parse();
		} else {
			var values = this.selectedOptions;
			var valuesCount = values.length;
			var hiddenCount = 0;
			if( valuesCount > 2 ) {
				values = values.slice( 0, 2 );
				hiddenCount = valuesCount - 2;
			}

			var labeledValues = [];
			for( var idx in values ) {
				var value = values[idx];
				for( var optionIdx in this.optionsCheckboxWidget.checkboxMultiselectWidget.items ) {
					var option = this.optionsCheckboxWidget.checkboxMultiselectWidget.items[optionIdx];
					if( option.data === value ) {
						labeledValues.push( option.label );
					}
				}
			}

			label = this.valueLabel + labeledValues.join( ', ' );
			if( hiddenCount > 0 ) {
				label += mw.message( this.hasHiddenLabelKey, hiddenCount ).parse();
			}
		}

		this.setLabel( label );
	}

	bs.extendedSearch.FilterWidget.prototype.onOptionsChange = function( oldValues, values ) {
		if( this.multiSelect === false ) {
			values = arrayDiff( values, oldValues );
			this.optionsCheckboxWidget.setValue( values );
		}

		this.selectedOptions = values;
		this.setFilterLabel();

		this.dirty = true;

		function arrayDiff( array1, array2 ) {
			return array1.filter( function( el ) {
				return array2.indexOf( el ) === -1;
			} );
		}
	};

	bs.extendedSearch.FilterAddWidget = function( cfg ) {
		cfg = cfg || {};
		cfg.framed = false;
		cfg.label = '';

		bs.extendedSearch.FilterAddWidget.parent.call( this, cfg );

		this.$element
			.attr( 'id', 'bs-extendedsearch-filter-add-button' )
			.attr( 'title', mw.message( 'bs-extendedsearch-filter-add-button-label' ).text() )
			.addClass( 'bs-extendedsearch-filter-add-widget tools-button' )
			.append( this.$button )
			.on( 'click', { cfg: cfg, parent: this }, this.openAddWidgetDialog );
	}

	OO.inheritClass( bs.extendedSearch.FilterAddWidget, OO.ui.ButtonWidget );

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

	bs.extendedSearch.FilterOptionsCheckboxWidget.prototype.setOptionsData = function ( options ) {
		var widget = this;

		this.optionsDirty = true;

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
	};

	bs.extendedSearch.FilterAndOrSwitch = function( cfg ) {
		cfg = cfg || {};

		this.orButton = new OO.ui.ButtonOptionWidget( {
			data: 'or',
			label: cfg.orLabel
		} );

		this.andButton = new OO.ui.ButtonOptionWidget( {
			data: 'and',
			label: cfg.andLabel
		} );

		cfg.items = [
			this.orButton,
			this.andButton
		];

		bs.extendedSearch.FilterAndOrSwitch.parent.call( this, cfg );

		this.selectItemByData( cfg.selected );

		this.$element.addClass( 'bs-extendedsearch-filter-and-or-switch' );
	}

	OO.inheritClass( bs.extendedSearch.FilterAndOrSwitch, OO.ui.ButtonSelectWidget );
} )( mediaWiki, jQuery, blueSpice, document );
