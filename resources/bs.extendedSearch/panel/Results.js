( function( mw, $, bs, d, undefined ) {
	bs.extendedSearch.ResultsPanel = function( cfg ) {
		cfg = cfg || {};

		this.$element = cfg.$element;

		bs.extendedSearch.ResultsPanel.parent.call( this, cfg );

		this.results = cfg.results;
		this.total = cfg.total;
		this.spellcheck = cfg.spellcheck;
		this.total_approximated = cfg.total_approximated;

		this.externalResults = cfg.externalResults || false;

		this.displayedResults = {};

		this.caller = cfg.caller;
		this.lookup = this.caller.getLookupObject() || null;

		this.mobile = cfg.mobile || false;

		this.showResults();
	};

	OO.inheritClass( bs.extendedSearch.ResultsPanel, OO.ui.Widget );

	bs.extendedSearch.ResultsPanel.prototype.showResults = function() {
		this.addResultsInternally( this.results );
		this.addLoadMoreButton();
	};

	bs.extendedSearch.ResultsPanel.prototype.addLoadMoreButton = function() {
		if( this.total <= Object.keys( this.displayedResults ).length ) {
			return;
		}
		this.loadMoreButton = new bs.extendedSearch.LoadMoreButtonWidget();
		this.loadMoreButton.$element.on( 'loadMore', this.loadMoreResults.bind( this ) );
		this.$element.append( this.loadMoreButton.$element );
	};

	bs.extendedSearch.ResultsPanel.prototype.appendResult = function( resultWidget ) {
		this.displayedResults[resultWidget.getId()] = resultWidget.getRawResult();
		this.$element.append( resultWidget.$element );
	};

	bs.extendedSearch.ResultsPanel.prototype.getDisplayedResults = function() {
		return this.displayedResults;
	};

	bs.extendedSearch.ResultsPanel.prototype.addResultsInternally = function( results ) {
		var me = this;

		$.each( results, function( idx, cfg ) {
			var resultWidget;

			if( me.externalResults ) {
				cfg.isExternal = true;
			}
			if( cfg.is_redirect ) {
				resultWidget = new bs.extendedSearch.ResultRedirectWidget( cfg, me.mobile );
			} else {
				resultWidget = new bs.extendedSearch.ResultWidget( cfg, me.mobile );
			}
			me.appendResult( resultWidget );
		} );

		this.emit( 'resultsAdded', results );
	};

	bs.extendedSearch.ResultsPanel.prototype.getLastShown = function() {
		if( this.displayedResults === {} ) {
			return null;
		}

		var lastKey = Object.keys( this.displayedResults )[Object.keys( this.displayedResults ).length - 1];
		return this.displayedResults[lastKey];
	};

	bs.extendedSearch.ResultsPanel.prototype.loadMoreResults = function( e ) {
		this.loadMoreButton.showLoading();

		var lastShown = this.getLastShown();
		if( !lastShown ) {
			this.loadMoreButton.error();
			return;
		}

		//We don't want to touch original lookup set in the URL hash
		var loadMoreLookup = $.extend( true, {}, this.lookup );
		loadMoreLookup.setSearchAfter( lastShown.search_more );

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
	};

} )( mediaWiki, jQuery, blueSpice, document );
