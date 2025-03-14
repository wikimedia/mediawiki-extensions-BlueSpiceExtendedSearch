( function ( $, bs ) {
	bs.extendedSearch.ResultsPanel = function ( cfg ) {
		cfg = cfg || {};

		this.$element = cfg.$element;

		bs.extendedSearch.ResultsPanel.parent.call( this, cfg );

		this.results = cfg.results;
		this.total = cfg.total;
		this.spellcheck = cfg.spellcheck;
		this.total_approximated = cfg.total_approximated; // eslint-disable-line camelcase
		this.searchAfter = cfg.searchAfter;

		this.externalResults = cfg.externalResults || false;

		this.displayedResults = {};

		this.caller = cfg.caller;
		this.lookup = this.caller.getLookupObject() || null;

		this.mobile = cfg.mobile || false;

		this.showResults();
	};

	OO.inheritClass( bs.extendedSearch.ResultsPanel, OO.ui.Widget );

	bs.extendedSearch.ResultsPanel.prototype.showResults = function () {
		this.addResultsInternally( this.results );
		this.addLoadMoreButton();
	};

	bs.extendedSearch.ResultsPanel.prototype.addLoadMoreButton = function () {
		if ( this.total <= Object.keys( this.displayedResults ).length ) {
			return;
		}
		this.loadMoreButton = new bs.extendedSearch.LoadMoreButtonWidget();
		this.loadMoreButton.$element.on( 'loadMore', this.loadMoreResults.bind( this ) );
		this.$element.append( this.loadMoreButton.$element );
	};

	bs.extendedSearch.ResultsPanel.prototype.appendResult = function ( resultWidget ) {
		this.displayedResults[ resultWidget.getId() ] = resultWidget.getRawResult();
		this.$element.append( resultWidget.$element );
	};

	bs.extendedSearch.ResultsPanel.prototype.getDisplayedResults = function () {
		return this.displayedResults;
	};

	bs.extendedSearch.ResultsPanel.prototype.getSearchAfter = function () {
		return this.searchAfter;
	};

	bs.extendedSearch.ResultsPanel.prototype.addResultsInternally = function ( results ) {
		const me = this;

		$.each( results, ( idx, cfg ) => { // eslint-disable-line no-jquery/no-each-util
			let resultWidget;

			if ( me.externalResults ) {
				cfg.isExternal = true;
			}
			if ( cfg.is_redirect ) {
				resultWidget = new bs.extendedSearch.ResultRedirectWidget( cfg, me.mobile );
			} else {
				resultWidget = new bs.extendedSearch.ResultWidget( cfg, me.mobile );
			}
			me.appendResult( resultWidget );
		} );

		this.emit( 'resultsAdded', results );
	};

	bs.extendedSearch.ResultsPanel.prototype.loadMoreResults = function ( e ) { // eslint-disable-line no-unused-vars
		this.loadMoreButton.showLoading();
		if ( !this.searchAfter ) {
			this.loadMoreButton.error();
			return;
		}

		// We don't want to touch original lookup set in the URL hash
		const loadMoreLookup = $.extend( true, {}, this.lookup );
		loadMoreLookup.setSearchAfter( this.searchAfter );

		const newResultsPromise = bs.extendedSearch.SearchCenter.runApiCall( {
			q: JSON.stringify( loadMoreLookup )
		} );

		const me = this;
		newResultsPromise.done( ( response ) => {
			if ( response.exception ) {
				return me.loadMoreButton.error();
			}
			me.searchAfter = response.search_after || null;

			const results = bs.extendedSearch.SearchCenter.applyResultsToStructure(
				response.results
			);

			me.loadMoreButton.destroy();
			me.addResultsInternally( results );

			me.addLoadMoreButton();
		} );
	};

}( jQuery, blueSpice ) );
