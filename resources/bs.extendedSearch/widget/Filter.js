( function ( mw, $, bs, d, undefined ) { // eslint-disable-line no-shadow-restricted-names
	bs.util.registerNamespace( 'bs.extendedSearch.mixin' );

	bs.extendedSearch.FilterWidget = function ( cfg ) {
		cfg = cfg || {};

		this.id = cfg.id;
		this.options = cfg.options || [];
		this.selectedOptions = cfg.selectedOptions || [];
		this.isANDEnabled = cfg.isANDEnabled === 1;
		this.multiSelect = cfg.multiSelect === 1;
		this.filterType = cfg.filterType || 'or';
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
		};

		bs.extendedSearch.FilterWidget.parent.call( this, cfg );

		OO.ui.mixin.ButtonElement.call( this, cfg );
		OO.ui.mixin.LabelElement.call( this, cfg );
		OO.ui.mixin.PopupElement.call( this, cfg );
		bs.extendedSearch.mixin.FilterRemoveButton.call( this, cfg );

		this.$button
			.addClass( 'bs-extendedsearch-filter-button-button' )
			.append( this.$label )
			.attr( 'tabindex', 0 );

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

		// PRESELECTED OPTIONS
		if ( this.selectedOptions.length > 0 ) {
			this.setFilterLabel();
		}
	};

	OO.inheritClass( bs.extendedSearch.FilterWidget, OO.ui.Widget );

	OO.mixinClass( bs.extendedSearch.FilterWidget, OO.ui.mixin.ButtonElement );
	OO.mixinClass( bs.extendedSearch.FilterWidget, OO.ui.mixin.LabelElement );
	OO.mixinClass( bs.extendedSearch.FilterWidget, OO.ui.mixin.PopupElement );
	OO.mixinClass( bs.extendedSearch.FilterWidget, bs.extendedSearch.mixin.FilterRemoveButton );

	bs.extendedSearch.FilterWidget.prototype.onShowOptions = function () {
		this.popup.toggle();
		this.isOpen = !this.isOpen;

		if ( this.dirty === false ) {
			return;
		}
		this.applyFilter();
	};

	bs.extendedSearch.FilterWidget.prototype.showOptions = function () {
		if ( this.isOpen === false ) {
			this.popup.toggle();
			this.isOpen = true;
		}
	};

	bs.extendedSearch.FilterWidget.prototype.applyFilter = function () {
		if ( this.selectedOptions.length === 0 ) {
			return this.removeFilter();
		}

		this.$element.trigger( 'filterOptionsChanged', {
			filterId: this.id,
			filterType: this.filterType,
			values: this.selectedOptions,
			options: this.options
		} );

		this.dirty = false;
	};

	bs.extendedSearch.FilterWidget.prototype.getPopupContentWidgetElement = function () {
		if ( this.options.length === 0 ) {
			return new OO.ui.LabelWidget( {
				label: mw.message( 'bs-extendedsearch-search-center-filter-no-options-label' ).plain()
			} ).$element;
		}

		this.$optionsContainer = $( '<div>' );

		this.filterBox = new OO.ui.SearchInputWidget();
		this.filterBox.on( 'change', () => {
			this.onOptionsFilter();
		} );

		this.applyFilterButton = new OO.ui.ButtonWidget( {
			label: 'OK',
			flags: 'primary'
		} );
		this.applyFilterButton.on( 'click', () => {
			this.onApplyFilterButton();
		} );

		let actionButton = this.applyFilterButton;
		if ( this.isANDEnabled ) {
			this.andOrSwitch = new bs.extendedSearch.FilterAndOrSwitch( {
				orLabel: mw.message( 'bs-extendedsearch-searchcenter-filter-or-label' ).plain(),
				andLabel: mw.message( 'bs-extendedsearch-searchcenter-filter-and-label' ).plain(),
				selected: this.filterType
			} );
			this.andOrSwitch.on( 'choose', ( e ) => {
				this.filterType = e.data;
			} );
			actionButton = new OO.ui.HorizontalLayout( {
				items: [ this.andOrSwitch, this.applyFilterButton ],
				classes: [ 'bs-extendedsearch-filter-horizontal-layout' ]
			} );
		}

		this.actions = new OO.ui.ActionFieldLayout( this.filterBox, actionButton, {
			align: 'inline',
			classes: [ 'bs-extendedsearch-filter-action-field-layout' ]
		} );

		this.$optionsContainer.append( this.actions.$element );

		this.addCheckboxWidget( this.options );

		return this.$optionsContainer;
	};

	bs.extendedSearch.FilterWidget.prototype.addCheckboxWidget = function ( options ) {
		this.optionsCheckboxWidget = new bs.extendedSearch.FilterOptionsCheckboxWidget( {
			value: this.selectedOptions,
			options: options
		} );

		this.optionsCheckboxWidget.$element.addClass( 'bs-extendedsearch-filter-options-checkbox-widget' );

		this.optionsCheckboxWidget.checkboxMultiselectWidget.on( 'change', () => {
			this.onOptionsChange( this.selectedOptions, this.optionsCheckboxWidget.checkboxMultiselectWidget.findSelectedItemsData() );
		} );

		this.$optionsContainer.append( this.optionsCheckboxWidget.$element );
	};

	bs.extendedSearch.FilterWidget.prototype.onOptionsFilter = function () {
		const searchTerm = this.filterBox.value;
		const filteredOptions = [];
		for ( let i = 0; i < this.options.length; i++ ) {
			const option = this.options[ i ];
			if ( this.selectedOptions.indexOf( option.data ) !== -1 ) {
				filteredOptions.push( option );
				continue;
			}

			if ( option.data.toLowerCase().includes( searchTerm.toLowerCase() ) ) {
				filteredOptions.push( option );
			}
		}
		this.$optionsContainer.children( '.bs-extendedsearch-filter-options-checkbox-widget' ).remove();
		this.addCheckboxWidget( filteredOptions );
	};

	bs.extendedSearch.FilterWidget.prototype.onApplyFilterButton = function () {
		this.applyFilter();
	};

	bs.extendedSearch.FilterWidget.prototype.setFilterLabel = function () {
		let label;
		if ( this.selectedOptions.length === 0 ) {
			label = this.emptyLabel;
		} else if ( this.mobile ) {
			const count = this.selectedOptions.length;
			label = this.valueLabel + mw.message( 'bs-extendedsearch-filter-label-count-only', count ).parse();
		} else {
			let values = this.selectedOptions;
			const valuesCount = values.length;
			let hiddenCount = 0;
			if ( valuesCount > 2 ) {
				values = values.slice( 0, 2 );
				hiddenCount = valuesCount - 2;
			}

			const labeledValues = [];
			for ( let i = 0; i < values.length; i++ ) {
				const value = values[ i ];
				for ( let optionIdx = 0; optionIdx < this.optionsCheckboxWidget.checkboxMultiselectWidget.items.length; optionIdx++ ) {
					const option = this.optionsCheckboxWidget.checkboxMultiselectWidget.items[ optionIdx ];
					const data = option.data;
					if ( data.toString() === value.toString() ) {
						labeledValues.push( option.label );
					}
				}
			}

			label = this.valueLabel + labeledValues.join( ', ' );
			if ( hiddenCount > 0 ) {
				const countMessageKey = this.hasHiddenLabelKey || '';
				label += mw.message( countMessageKey, hiddenCount ).parse(); // eslint-disable-line mediawiki/msg-doc
			}
		}

		this.setLabel( label );
	};

	bs.extendedSearch.FilterWidget.prototype.onOptionsChange = function ( oldValues, values ) {
		if ( this.multiSelect === false ) {
			values = arrayDiff( values, oldValues );
			this.optionsCheckboxWidget.setValue( values );
		}

		this.selectedOptions = values;
		this.setFilterLabel();

		this.dirty = true;

		function arrayDiff( array1, array2 ) {
			return array1.filter( ( el ) => array2.indexOf( el ) === -1 );
		}
	};

	bs.extendedSearch.FilterOptionsCheckboxWidget = function ( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterOptionsCheckboxWidget.parent.call( this, cfg );

		this.$element.addClass( 'bs-extendedsearch-filter-group' );
	};

	OO.inheritClass( bs.extendedSearch.FilterOptionsCheckboxWidget, OO.ui.CheckboxMultiselectInputWidget );

	bs.extendedSearch.FilterOptionsCheckboxWidget.prototype.setOptionsData = function ( options ) {
		const widget = this;

		this.optionsDirty = true;

		this.checkboxMultiselectWidget
			.clearItems()
			.addItems( options.map( ( opt ) => {
				const optValue = OO.ui.CheckboxMultiselectInputWidget.parent.prototype.cleanUpValue.call( widget, opt.data );
				const optDisabled = opt.disabled !== undefined ? opt.disabled : false;
				const item = new OO.ui.CheckboxMultioptionWidget( {
					data: optValue,
					label: opt.label !== undefined ? opt.label : optValue,
					disabled: optDisabled
				} );
				// Set the 'name' and 'value' for form submission
				item.checkbox.$input.attr( 'name', widget.inputName );
				item.checkbox.setValue( optValue );
				item.$element
					.append(
						$( '<p>' )
							.html( opt.count )
							.addClass( 'bs-extendedsearch-filter-option-count' )
					)
					.addClass( 'bs-extendedsearch-filter-option' );
				return item;
			} ) );
	};

	bs.extendedSearch.FilterAndOrSwitch = function ( cfg ) {
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
	};

	OO.inheritClass( bs.extendedSearch.FilterAndOrSwitch, OO.ui.ButtonSelectWidget );
}( mediaWiki, jQuery, blueSpice, document ) );
