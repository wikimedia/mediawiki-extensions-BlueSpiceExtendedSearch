( function ( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.extendedsearch.report' );

	bs.extendedsearch.report.SearchTermsReport = function ( cfg ) {
		bs.extendedsearch.report.SearchTermsReport.parent.call( this, cfg );
	};

	OO.inheritClass( bs.extendedsearch.report.SearchTermsReport, bs.aggregatedStatistics.report.ReportBase );

	bs.extendedsearch.report.SearchTermsReport.static.label = mw.message( 'bs-extendedsearch-statistics-report-search-terms' ).text();

	bs.extendedsearch.report.SearchTermsReport.static.desc = mw.message( 'bs-extendedsearch-statistics-report-search-terms-desc' ).text();

	bs.extendedsearch.report.SearchTermsReport.prototype.getFilters = function () {
		return [];
	};

	bs.extendedsearch.report.SearchTermsReport.prototype.isAggregate = function () {
		return true;
	};

	bs.extendedsearch.report.SearchTermsReport.prototype.getChart = function () {
		return new bs.aggregatedStatistics.charts.Barchart();
	};

	bs.extendedsearch.report.SearchTermsReport.prototype.getAxisLabels = function () {
		return {
			value: mw.message( 'bs-extendedsearch-statistics-report-search-terms-axis-label' ).text()
		};
	};

}( mediaWiki, jQuery, blueSpice ) );
