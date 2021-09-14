<?php

namespace BS\ExtendedSearch\Statistics\Report;

use BlueSpice\ExtendedStatistics\ClientReportHandler;
use BlueSpice\ExtendedStatistics\IReport;

class SearchTerms implements IReport {

	/**
	 * @inheritDoc
	 */
	public function getSnapshotKey() {
		return 'es-searchstats';
	}

	/**
	 * @inheritDoc
	 */
	public function getClientData( $snapshots, array $filterData, $limit = 20 ): array {
		// This report is always aggregated
		$snapshot = $snapshots[0];
		$data = $snapshot->getData();
		$terms = $data['terms'];
		uasort( $terms, static function ( $a, $b ) {
			if ( $a == $b ) {
				return 0;
			}
			return ( $a < $b ) ? 1 : -1;
		} );
		$terms = array_slice( $terms, 0, $limit );
		$processed = [];
		foreach ( $terms as $term => $hits ) {
			$processed[] = [
				'name' => $term,
				'value' => $hits
			];
		}

		return $processed;
	}

	/**
	 * @inheritDoc
	 */
	public function getClientReportHandler(): ClientReportHandler {
		return new ClientReportHandler(
			[ 'ext.bluespice.extendedsearch.statistics' ],
			'bs.extendedsearch.report.SearchTermsReport'
		);
	}
}
