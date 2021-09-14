(function ( mw, $, bs) {
	bs.util.registerNamespace( 'bs.extendedsearch.report' );

	bs.extendedsearch.report.SearchTermsByUserReport = function ( cfg ) {
		bs.extendedsearch.report.SearchTermsByUserReport.parent.call( this, cfg );
	};

	OO.inheritClass( bs.extendedsearch.report.SearchTermsByUserReport, bs.aggregatedStatistics.report.ReportBase );

	bs.extendedsearch.report.SearchTermsByUserReport.static.label = mw.message( "bs-extendedsearch-statistics-report-search-term-by-user" ).text();

	bs.extendedsearch.report.SearchTermsByUserReport.prototype.getFilters = function () {
		return [
			new bs.aggregatedStatistics.filter.UserFilter( { required: true } )
		];
	};

	bs.extendedsearch.report.SearchTermsByUserReport.prototype.isAggregate = function () {
		return true;
	};

	bs.extendedsearch.report.SearchTermsByUserReport.prototype.getChart = function () {
		return new bs.aggregatedStatistics.charts.Barchart();
	};

} )( mediaWiki, jQuery , blueSpice);