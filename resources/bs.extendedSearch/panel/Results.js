( function( mw, $, bs, d, undefined ) {
	bs.extendedSearch.ResultsPanel = function() {
	}

	bs.extendedSearch.ResultsPanel.prototype.init = function( cfg ) {
		this.removeLoading();

		this.results = cfg.results;
		this.total = cfg.total;
		this.spellcheck = cfg.spellcheck;
		this.total_approximated = cfg.total_approximated;

		this.displayedResults = {};

		if( this.total == 0 ) {
			return this.showNoResults();
		}

		this.caller = cfg.caller;
		this.lookup = this.caller.getLookupObject() || null;

		this.mobile = cfg.mobile || false;

		this.showResults();
	}

	bs.extendedSearch.ResultsPanel.prototype.showResults = function() {
		this.addResultsInternally( this.results );
		this.addLoadMoreButton();
	}

	bs.extendedSearch.ResultsPanel.prototype.addLoadMoreButton = function() {
		if( this.total <= Object.keys( this.displayedResults ).length ) {
			return;
		}
		this.loadMoreButton = new bs.extendedSearch.LoadMoreButtonWidget();
		this.loadMoreButton.$element.on( 'loadMore', this.loadMoreResults.bind( this ) );
		$( '#bs-es-results' ).append( this.loadMoreButton.$element );
	}

	bs.extendedSearch.ResultsPanel.prototype.appendResult = function( resultWidget ) {
		this.displayedResults[resultWidget.getId()] = resultWidget.getRawResult();
		$( '#bs-es-results' ).append( resultWidget.$element );
	}

	bs.extendedSearch.ResultsPanel.prototype.clearResults = function() {
		this.displayedResults = {};
		$( '#bs-es-results' ).html('');
	}

	bs.extendedSearch.ResultsPanel.prototype.clearAll = function() {
		this.clearResults();
		$( '#bs-es-tools' ).html('');
	}

	bs.extendedSearch.ResultsPanel.prototype.showNoResults = function() {
		$( '#bs-es-results' ).html(
			$( '<div>' )
			.addClass( 'bs-extendedsearch-no-results' )
			.html(
				$( '<span>' ).html( mw.message( 'bs-extendedsearch-search-center-result-no-results' ).plain() )
			)
		);
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
		$( '#bs-es-tools, #bs-es-results' ).hide();
	}

	bs.extendedSearch.ResultsPanel.prototype.removeLoading = function() {
		$( '.bs-extendedsearch-searchcenter-loading' ).remove();
		$( '#bs-es-tools, #bs-es-results' ).show();
	}

	bs.extendedSearch.ResultsPanel.prototype.showHelp = function() {
		//SearchCenter SP should only be navigated to using main search bar,
		//if user arrives on it directly, show usage help
		this.removeLoading();

		this.showMessage( mw.message( 'bs-extendedsearch-search-center-result-help' ).plain() );
	}

	bs.extendedSearch.ResultsPanel.prototype.showError = function() {
		this.removeLoading();

		this.showMessage( mw.message( 'bs-extendedsearch-search-center-result-exception' ).plain() );
	}

	bs.extendedSearch.ResultsPanel.prototype.addResultsInternally = function( results ) {
		var me = this;

		$.each( results, function( idx, cfg ) {
			var resultWidget;
			if( cfg.is_redirect ) {
				resultWidget = new bs.extendedSearch.ResultRedirectWidget( cfg, me.mobile );
			} else {
				resultWidget = new bs.extendedSearch.ResultWidget( cfg, me.mobile );
			}
			me.appendResult( resultWidget );
		} );
	}

	bs.extendedSearch.ResultsPanel.prototype.showMessage = function( message ) {
		$( '#bs-es-results' ).html(
			$( '<div>' )
				.addClass( 'bs-extendedsearch-help' )
				.html(
					$( '<span>' ).html( message )
				)
		);
	}

	bs.extendedSearch.ResultsPanel.prototype.getLastShown = function() {
		if( this.displayedResults == {} ) {
			return null;
		}

		var lastKey = Object.keys( this.displayedResults )[Object.keys( this.displayedResults ).length - 1];
		return this.displayedResults[lastKey];
	}

	bs.extendedSearch.ResultsPanel.prototype.loadMoreResults = function( e ) {
		this.loadMoreButton.showLoading();

		var lastShown = this.getLastShown();
		if( !lastShown ) {
			this.loadMoreButton.error();
			return;
		}

		var searchAfter = [];

		//We dont want to touch original lookup set in the URL hash
		var loadMoreLookup = $.extend( true, {}, this.lookup );
		var sortFields = loadMoreLookup.getSort();
		for( var idx in sortFields ) {
			for( field in sortFields[idx] ) {
				if( field.charAt( 0 ) == '_' ) {
					field = field.slice( 1 );
				}

				searchAfter.push( lastShown[field] );
			}
		}
		searchAfter.push( lastShown.id );
		loadMoreLookup.setSearchAfter( searchAfter );

		var newResultsPromise = bs.extendedSearch.SearchCenter.runApiCall( {
			q: JSON.stringify( loadMoreLookup )
		} );

		var me = this;
		newResultsPromise.done( function( response ) {
			if( response.exception ) {
				return me.loadMoreButton.error();
			}

			var results = bs.extendedSearch.SearchCenter.applyResultsToStructure(
				response.results
			);

			me.loadMoreButton.destroy();
			me.addResultsInternally( results );

			me.addLoadMoreButton();
		} );
	}

} )( mediaWiki, jQuery, blueSpice, document );