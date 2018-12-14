( function( mw, $, bs, d, undefined ) {
	bs.extendedSearch.ResultMessage = function( cfg ) {
		this.mode = cfg.mode;

		bs.extendedSearch.ResultMessage.parent.call( this, cfg );

		if( this.mode === 'help' ) {
			this.showHelp();
		}
		if( this.mode === 'error' ) {
			this.showError();
		}
		if( this.mode === 'noResults' ) {
			this.showNoResults();
		}
	};

	OO.inheritClass( bs.extendedSearch.ResultMessage, OO.ui.Widget );

	bs.extendedSearch.ResultMessage.static.tagName = 'div';

	bs.extendedSearch.ResultMessage.prototype.showHelp = function() {
		var label = new OO.ui.LabelWidget( {
			classes: [ 'bs-extendedsearch-help' ],
			label: mw.message( 'bs-extendedsearch-search-center-result-help' ).text()
		} );
		this.$element.append( label.$element );
	};

	bs.extendedSearch.ResultMessage.prototype.showNoResults = function() {
		var label = new OO.ui.LabelWidget( {
			classes: [ 'bs-extendedsearch-no-results' ],
			label: mw.message( 'bs-extendedsearch-search-center-result-no-results' ).text()
		} );
		this.$element.append( label.$element );
	};

	bs.extendedSearch.ResultMessage.prototype.showError = function() {
		var label = new OO.ui.LabelWidget( {
			classes: [ 'bs-extendedsearch-help' ],
			label: mw.message( 'bs-extendedsearch-search-center-result-exception' ).plain()
		} );
		this.$element.append( label.$element );
	};

} )( mediaWiki, jQuery, blueSpice, document );


