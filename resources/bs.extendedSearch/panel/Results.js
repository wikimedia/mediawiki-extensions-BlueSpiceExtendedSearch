( function( mw, $, bs, d, undefined ) {
	bs.extendedSearch.ResultsPanel = function() {
	}

	bs.extendedSearch.ResultsPanel.prototype.init = function( cfg ) {
		this.removeLoading();

		this.results = cfg.results;
		this.total = cfg.total;

		if( this.total == 0 ) {
			return this.showNoResults();
		}

		this.caller = cfg.caller;
		this.lookup = this.caller.getLookupObject() || null;

		this.showResults();
	}

	bs.extendedSearch.ResultsPanel.prototype.showResults = function() {
		var me = this;

		var term = me.lookup.getSimpleQueryString().query || '';

		var hitCountWidget = new bs.extendedSearch.HitCountWidget({
			term: term,
			count: me.total
		});

		$('#bs-es-hitcount' ).append( hitCountWidget.$element );
		$.each( this.results, function( idx, cfg ) {
			var resultWidget = new bs.extendedSearch.ResultWidget( cfg );
			me.appendResult( resultWidget.$element );
		} );

		this.paginator = new bs.extendedSearch.PaginatorWidget( {
			total: me.total,
			size: me.lookup.getSize(),
			from: me.lookup.getFrom()
		} );

		this.paginator.$element.on( 'changePage', me.changePagination.bind( this ) );
		me.appendResult( this.paginator.$element );
	}

	bs.extendedSearch.ResultsPanel.prototype.appendResult = function( resultWidget ) {
		$( '#bs-es-results' ).append( resultWidget );
	}

	bs.extendedSearch.ResultsPanel.prototype.clearResults = function() {
		$( '#bs-es-results' ).html('');
	}

	bs.extendedSearch.ResultsPanel.prototype.clearAll = function() {
		this.clearResults();
		$( '#bs-es-tools' ).html('');
		$( '#bs-es-hitcount' ).html('');
	}

	bs.extendedSearch.ResultsPanel.prototype.showNoResults = function() {
		$( '#bs-es-tools' ).addClass( 'bs-extendedsearch-tools-when-no-results' );
		$( '#bs-es-results' ).html(
			$( '<div>' )
			.addClass( 'bs-extendedsearch-no-results' )
			.html(
				$( '<span>' ).html( mw.message( 'bs-extendedsearch-search-center-result-no-results' ).plain() )
			)
		);
	}

	bs.extendedSearch.ResultsPanel.prototype.changePagination = function( e, targetPage ) {
		if( targetPage.current ) {
			return;
		}
		this.lookup.setFrom( targetPage.from );
		this.caller.updateQueryHash();
	}

	bs.extendedSearch.ResultsPanel.prototype.showLoading = function() {
		//by not removing and re-adding on every change we achieve continuos
		//loading until there is something to show
		if( $( '.bs-extendedsearch-searchcenter-loading' ).length > 0 ) {
			return;
		}

		var pbWidget = new OO.ui.ProgressBarWidget({
			progress: false
		} );

		//Insert loader before results div to avoid reseting it
		$( '#bs-es-results' ).before(
			$( '<div>' )
				.addClass( 'bs-extendedsearch-searchcenter-loading' )
				.append( pbWidget.$element )
		);
	}

	bs.extendedSearch.ResultsPanel.prototype.removeLoading = function() {
		$( '.bs-extendedsearch-searchcenter-loading' ).remove();
	}

	bs.extendedSearch.ResultsPanel.prototype.showHelp = function() {
		//SearchCenter SP should only be navigated to using main search bar,
		//if user arrives on it directly, show usage help
		this.removeLoading();

		$( '#bs-es-results' ).html(
			$( '<div>' )
				.addClass( 'bs-extendedsearch-help' )
				.html(
					$( '<span>' ).html( mw.message( 'bs-extendedsearch-search-center-result-help' ).plain() )
				)
		);
	}
} )( mediaWiki, jQuery, blueSpice, document );