( function( mw, $, bs, d, undefined ){

	bs.util.registerNamespace( "bs.extendedSearch.mixin" );

	bs.extendedSearch.PaginatorWidget = function( cfg ) {
		cfg = cfg || {};

		this.$element = $( '<div>' );

		bs.extendedSearch.PaginatorWidget.parent.call( this, cfg );

		this.$element.addClass( 'bs-extendedsearch-paginator' );

		this.total = cfg.total;
		this.total = parseInt( this.total );
		if( isNaN( this.total ) ) {
			return;
		}

		if( this.total == 0 ) {
			return;
		}

		this.size = cfg.size || 0;
		this.size = parseInt( this.size );

		if( isNaN( this.size ) ) {
			return;
		}

		if( this.size <= 0 ) {
			return;
		}

		this.from = cfg.from;

		if( this.size >= this.total ) {
			return;
		}
		this.pages = [];
		this.currentPage = {};

		var resultsDone = -1;
		while( resultsDone < this.total - 1 ) {
			var page = {
				from: resultsDone + 1 ,
				to: resultsDone + this.size,
				current: false
			};
			resultsDone += this.size;
			if( resultsDone > this.total ) {
				page.to = this.total;
			}
			if( page.from <= this.from && page.to >= this.from ) {
				page.current = true;
				this.currentPage = page;
			}
			this.pages.push( page );

			if( this.pages.length > 1000 ) {
				break;
			}
		}

		this.insertedIndex = null;

		var currentPageIndex = this.pages.indexOf( this.currentPage );

		for( pageIdx in this.pages ) {
			var page = this.pages[pageIdx];
			if( pageIdx == 0 && page.current == false ) {
				this.$element.append( new OO.ui.ButtonWidget( {
						label: '<'
					} ).$element.on( 'click', { currentPage: this.currentPage, pages: this.pages, paginator: this.$element }, this.toPrevPage )
				);
			}

			if( pageIdx > 3 && pageIdx < this.pages.length - 3 ) {
				if( pageIdx >= currentPageIndex + 2 || pageIdx <= currentPageIndex - 2 ) {
					continue;
				}
			}
			if( this.insertedIndex && ( pageIdx - this.insertedIndex ) > 1 ) {
				this.$element.append(
					new OO.ui.LabelWidget( {
						label: '...'
					} ).$element.addClass( 'bs-extendedsearch-paginator-separator' )
				);
			}

			this.insertedIndex = pageIdx;
			var button = new OO.ui.ButtonWidget( {
				label: ( parseInt( pageIdx ) + 1 ).toString()
			} );
			button.$element.on( 'click', { targetIdx: pageIdx, pages: this.pages, paginator: this.$element }, this.changePage );

			if( page.current == true ) {
				button.setDisabled( true );
				button.$element.addClass( 'bs-extendedsearch-paginator-button-current' );
				this.currentPageButton = button;
			}
			this.$element.append( button.$element );

			if( pageIdx == this.pages.length - 1 && page.current == false ) {
				this.$element.append( new OO.ui.ButtonWidget( {
						label: '>'
					} ).$element.on( 'click', { currentPage: this.currentPage, pages: this.pages, paginator: this.$element }, this.toNextPage )
				);
			}
		}

	}

	OO.inheritClass( bs.extendedSearch.PaginatorWidget, OO.ui.Widget );

	bs.extendedSearch.PaginatorWidget.prototype.toPrevPage = function( e ) {
		var currentPageIndex = e.data.pages.indexOf( e.data.currentPage );
		if( currentPageIndex > 1 ) {
			return;
		}
		var targetPage = e.data.pages[currentPageIndex - 1];
		if( !targetPage ) {
			return;
		}
		e.data.paginator.trigger( 'changePage', targetPage );
	}

	bs.extendedSearch.PaginatorWidget.prototype.toNextPage = function( e ) {
		var currentPageIndex = e.data.pages.indexOf( e.data.currentPage );
		if( currentPageIndex >= e.data.pages.length ) {
			return;
		}
		var targetPage = e.data.pages[currentPageIndex + 1];
		if( !targetPage ) {
			return;
		}
		e.data.paginator.trigger( 'changePage', targetPage );
	}

	bs.extendedSearch.PaginatorWidget.prototype.changePage = function( e ) {
		var targetPage = e.data.pages[e.data.targetIdx];
		e.data.paginator.trigger( 'changePage', targetPage );
	}

} )( mediaWiki, jQuery, blueSpice, document );