( function( mw, $, bs, d, undefined ){
	bs.extendedSearch.FilterAddDialog = function( cfg ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterAddDialog.super.call( this, cfg );

		this.maxHeight = $( window ).outerHeight();
		this.lastHeight = 0;

		this.availableFilters = cfg.filterData || [];
		this.groupFilters();

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

	bs.extendedSearch.FilterAddDialog.prototype.addFilters = function() {
		for( var group in this.groupedFilters ) {
			if ( !this.groupedFilters.hasOwnProperty( group ) ) {
				continue;
			}
			if( group === 'root' ) {
				this.addChildFilters( this.groupedFilters[group], this.content.$element );
				continue;
			}
			var $group = new bs.extendedSearch.FilterAddDialogGroup( group, this ).$element;

			var $groupItems = $( '<div>' ).addClass( 'bs-extendedsearch-addfilter-group-items' );
			this.addChildFilters( this.groupedFilters[group], $groupItems );

			$group.append( $groupItems );
			this.content.$element.append( $group );
		}
	}

	bs.extendedSearch.FilterAddDialog.prototype.addChildFilters = function( filters, $element ) {
		for ( var i = 0; i < filters.length; i++ ) {
			var filter = filters[i];
			filter.disabled = false;

			if( $( '#bs-extendedsearch-filter-' + filter.filter.id ).length > 0 ) {
				filter.disabled = true;
			}
			var $filter = new bs.extendedSearch.FilterAddDialogItem( filter, this ).$element;

			$element.append( $filter );
		}
	}

	bs.extendedSearch.FilterAddDialog.prototype.groupFilters = function() {
		this.groupedFilters = {};
		for( var i = 0; i < this.availableFilters.length; i++ ) {
			var filter = this.availableFilters[i];

			if( !filter.group ) {
				filter.group = 'root';
			}

			if( Array.isArray( this.groupedFilters[filter.group] ) ) {
				this.groupedFilters[filter.group].push( filter );
			} else {
				this.groupedFilters[filter.group] = [filter];
			}
		}
	}

	bs.extendedSearch.FilterAddDialog.prototype.getBodyHeight = function () {
		var height = this.content.$element.outerHeight();
		this.lastHeight = height;
		return height;
	};

	bs.extendedSearch.FilterAddDialogItem = function( cfg, window ) {
		cfg = cfg || {};

		bs.extendedSearch.FilterAddDialogItem.parent.call( this, cfg );

		OO.ui.mixin.ButtonElement.call( this, cfg );
		OO.ui.mixin.LabelElement.call( this, cfg );

		this.$button
			.append( this.$label )
			.attr( 'tabindex', 0 );

		this.$element
			.addClass( 'bs-extendedsearch-filter-add-dialog-item' )
			.append( this.$button )
			.on( 'click', { cfg: cfg, window: window }, this.filterToAddSelected )
			.on( 'keydown',  { cfg: cfg, window: window }, function( e ) {
				if ( e.which === OO.ui.Keys.ENTER ) {
					this.filterToAddSelected( e );
				}
			}.bind( this ) );
	}

	OO.inheritClass( bs.extendedSearch.FilterAddDialogItem, OO.ui.Widget );

	OO.mixinClass( bs.extendedSearch.FilterAddDialogItem, OO.ui.mixin.ButtonElement );
	OO.mixinClass( bs.extendedSearch.FilterAddDialogItem, OO.ui.mixin.LabelElement );

	bs.extendedSearch.FilterAddDialogItem.prototype.filterToAddSelected = function( e ) {
		if ( !e.data.cfg.disabled && ( e.which === OO.ui.MouseButtons.LEFT || e.which === OO.ui.Keys.ENTER ) ) {
			e.data.window.parentButton.trigger( 'widgetToAddSelected', { cfg: e.data.cfg.filter, window: e.data.window } );
		}
	}

	bs.extendedSearch.FilterAddDialogGroup = function( group, window ) {
		var cfg = {
			label: mw.message( 'bs-extendedsearch-add-filter-group-' + group + '-label' ).plain(),
			indicator: 'down'
		}

		bs.extendedSearch.FilterAddDialogGroup.parent.call( this, cfg );

		OO.ui.mixin.ButtonElement.call( this, cfg );
		OO.ui.mixin.LabelElement.call( this, cfg );
		OO.ui.mixin.IndicatorElement.call( this, cfg );

		this.$button
			.append( this.$label )
			.append( this.$indicator )
			.attr( 'tabindex', 0 );

		this.$element
			.addClass( 'bs-extendedsearch-filter-add-dialog-group' )
			.append( this.$button )
			.on( 'click', { window: window }, this.toggleGroup.bind( this ) )
			.on( 'keydown',  { window: window }, function( e ) {
				if ( e.which === OO.ui.Keys.ENTER ) {
					this.toggleGroup( e );
				}
			}.bind( this ) );
	}

	OO.inheritClass( bs.extendedSearch.FilterAddDialogGroup, OO.ui.Widget );

	OO.mixinClass( bs.extendedSearch.FilterAddDialogGroup, OO.ui.mixin.ButtonElement );
	OO.mixinClass( bs.extendedSearch.FilterAddDialogGroup, OO.ui.mixin.LabelElement );
	OO.mixinClass( bs.extendedSearch.FilterAddDialogGroup, OO.ui.mixin.IndicatorElement );

	bs.extendedSearch.FilterAddDialogGroup.prototype.toggleGroup = function( e ) {
		if ( e.which === OO.ui.MouseButtons.LEFT || e.which === OO.ui.Keys.ENTER ) {
			var $items = this.$element.find( ".bs-extendedsearch-addfilter-group-items" );

			var currentWindowHeight = e.data.window.$frame.outerHeight();
			//Leave some space, dont show dialog egde-to-edge
			var maxHeight = e.data.window.maxHeight - 130;

			if( $items.is( ':hidden' ) ) {
				this.$element.addClass( 'opened' );
				this.setIndicator( 'up' );
				$items.slideDown( 300, function() {
					e.data.window.lastHeight = currentWindowHeight;
					var newHeight = currentWindowHeight + $items.outerHeight();
					if( newHeight > maxHeight ) {
						newHeight = maxHeight;
					}
					e.data.window.$frame.outerHeight( newHeight );
				} );
			} else {
				this.$element.removeClass( 'opened' );
				this.setIndicator( 'down' );
				e.data.window.$frame.outerHeight( e.data.window.lastHeight );
				$items.slideUp( 300 );
			}
		}
	}
} )( mediaWiki, jQuery, blueSpice, document );
