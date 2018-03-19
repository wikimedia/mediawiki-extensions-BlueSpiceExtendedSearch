( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.FilterAddDialog = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterAddDialog.super.call( this, cfg );

		this.availableFilters = cfg.filterData || [];
		this.parentButton = cfg.parentButton;
	}

	OO.inheritClass( bs.extendedSearch.FilterAddDialog, OO.ui.ProcessDialog );

	bs.extendedSearch.FilterAddDialog.static.name = 'addFilter';

	bs.extendedSearch.FilterAddDialog.static.title = mw.message( 'bs-extendedsearch-search-center-addfilter-dialog-title' ).plain();

	bs.extendedSearch.FilterAddDialog.static.actions = [
		{
			label: mw.message( 'bs-extendedsearch-search-center-dialog-button-cancel-label' ).plain(),
			flags: 'safe'
		}
	];

	bs.extendedSearch.FilterAddDialog.prototype.initialize = function() {
		bs.extendedSearch.FilterAddDialog.super.prototype.initialize.call( this );

		this.content = new OO.ui.PanelLayout( {
			$: this.$,
			padded: false,
			scrollable: true,
			expanded: false,
			framed: false
		} );

		this.addFilters();

		this.$body.append( this.content.$element );
	}

	bs.extendedSearch.FilterAddDialog.prototype.addFilters = function () {
		for( idx in this.availableFilters ) {
			var filter = this.availableFilters[ idx ];
			filter.disabled = false;

			if( $( '#bs-extendedSearch-filter-' + filter.filter.id ).length > 0 ) {
				filter.disabled = true;
			}
			var item = new bs.extendedSearch.FilterAddDialogItem( filter, this ).$element;

			this.content.$element.append(
				item
			);
		}
	}

	bs.extendedSearch.FilterAddDialog.prototype.getBodyHeight = function () {
		return this.content.$element.outerHeight();
	};

	bs.extendedSearch.FilterAddDialogItem = function( cfg, window ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterAddDialogItem.parent.call( this, cfg );

		OO.ui.mixin.ButtonElement.call( this, cfg );
		OO.ui.mixin.LabelElement.call( this, cfg );

		this.$button.append( this.$label );

		this.$element
			.addClass( 'bs-extendedsearch-filter-add-dialog-item' )
			.append( this.$button )
			.on( 'click', { cfg: cfg, window: window }, this.filterToAddSelected );
	}

	OO.inheritClass( bs.extendedSearch.FilterAddDialogItem, OO.ui.Widget );

	OO.mixinClass( bs.extendedSearch.FilterAddDialogItem, OO.ui.mixin.ButtonElement );
	OO.mixinClass( bs.extendedSearch.FilterAddDialogItem, OO.ui.mixin.LabelElement );

	bs.extendedSearch.FilterAddDialogItem.prototype.filterToAddSelected = function( e ) {
		if ( !e.data.cfg.disabled && e.which === OO.ui.MouseButtons.LEFT ) {
			e.data.window.parentButton.trigger( 'widgetToAddSelected', { cfg: e.data.cfg.filter, window: e.data.window } );
		}
	}
} )( mediaWiki, jQuery, blueSpice, document );
