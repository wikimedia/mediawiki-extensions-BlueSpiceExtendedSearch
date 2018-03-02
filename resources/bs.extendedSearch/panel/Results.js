( function( mw, $, bs, d, undefined ) {

	function _showResults( results, total ) {
		var me = this;

		var lookup = bs.extendedSearch.SearchCenter.getLookupObject();

		var term = lookup.getSimpleQueryString().query || '';

		var hitCountWidget = new bs.extendedSearch.HitCountWidget({
			term: term,
			count: total
		});

		$('#bs-es-hitcount' ).append( hitCountWidget.$element );
		$.each( results, function( idx, cfg ) {
			var resultWidget = new bs.extendedSearch.ResultWidget( cfg );
			me.appendResult( resultWidget.$element );
		} );

		this.paginator = new bs.extendedSearch.PaginatorWidget( {
			total: total,
			size: lookup.getSize(),
			from: lookup.getFrom()
		} );

		this.paginator.$element.on( 'changePage', changePagination );
		me.appendResult( this.paginator.$element );
	}

	function changePagination( e, targetPage ) {
		if( targetPage.current ) {
			return;
		}
		bs.extendedSearch.SearchCenter.getLookupObject().setFrom( targetPage.from );
		bs.extendedSearch.SearchCenter.updateQueryHash();
	}

	function _appendResult( resultWidget ) {
		$ ( '#bs-es-results' ).append( resultWidget );
	}

	function _clearAll() {
		clearResults();
		$( '#bs-es-tools' ).html('');
		$( '#bs-es-hitcount' ).html('');
	}

	function clearResults() {
		$( '#bs-es-results' ).html('');
	}

	function _showNoResults() {
		$( '#bs-es-tools' ).addClass( 'bs-extendedsearch-tools-when-no-results' );
		$( '#bs-es-results' ).html(
			$( '<div>' )
			.addClass( 'bs-extendedsearch-no-results' )
			.html(
				$( '<span>' ).html( mw.message( 'bs-extendedsearch-search-center-result-no-results' ).plain() )
			)
		);
	}

	bs.extendedSearch.ResultsPanel = {
		showResults: _showResults,
		appendResult: _appendResult,
		clearAll: _clearAll,
		showNoResults: _showNoResults
	}
} )( mediaWiki, jQuery, blueSpice, document );