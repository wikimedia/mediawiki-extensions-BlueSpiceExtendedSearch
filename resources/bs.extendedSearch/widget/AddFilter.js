bs.util.registerNamespace( 'bs.extendedSearch' );

bs.extendedSearch.FilterAddWidget = function ( cfg ) {
	cfg = cfg || {};
	cfg.label = mw.message( 'bs-extendedsearch-filter-add-button-label' ).text();
	cfg.icon = 'funnel';

	this.availableFilters = cfg.filterData || [];
	this.activeFilters = cfg.activeFilters || [];
	this.groupFilters();

	const menuItems = [];
	for ( const group in this.groupedFilters ) {
		if ( !this.groupedFilters.hasOwnProperty( group ) ) {
			continue;
		}
		if ( group !== 'root' ) {
			menuItems.push( new OO.ui.MenuSectionOptionWidget( {
				label: mw.message( 'bs-extendedsearch-add-filter-group-' + group + '-label' ).plain() // eslint-disable-line mediawiki/msg-doc
			} ) );
		}

		for ( let i = 0; i < this.groupedFilters[ group ].length; i++ ) {
			const filter = this.groupedFilters[ group ][ i ];
			menuItems.push( new OO.ui.MenuOptionWidget( {
				data: filter.filter,
				disabled: this.activeFilters.indexOf( filter.filter.id ) !== -1,
				label: filter.label
			} ) );
		}
	}

	cfg.menu = { horizontalPosition: 'end', items: menuItems };

	bs.extendedSearch.FilterAddWidget.parent.call( this, cfg );
	this.menu.connect( this, { select: 'addFilter' } );
};

OO.inheritClass( bs.extendedSearch.FilterAddWidget, OO.ui.ButtonMenuSelectWidget );

bs.extendedSearch.FilterAddWidget.prototype.addFilter = function ( item ) {
	if ( !item ) {
		return;
	}
	this.emit( 'addFilter', item.getData() );
};

bs.extendedSearch.FilterAddWidget.prototype.groupFilters = function () {
	this.groupedFilters = {};
	for ( let i = 0; i < this.availableFilters.length; i++ ) {
		const filter = this.availableFilters[ i ];

		if ( !filter.group ) {
			filter.group = 'root';
		}

		if ( Array.isArray( this.groupedFilters[ filter.group ] ) ) {
			this.groupedFilters[ filter.group ].push( filter );
		} else {
			this.groupedFilters[ filter.group ] = [ filter ];
		}
	}
};
