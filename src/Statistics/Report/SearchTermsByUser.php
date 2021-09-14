<?php

namespace BS\ExtendedSearch\Statistics\Report;

use BlueSpice\ExtendedStatistics\ClientReportHandler;

class SearchTermsByUser extends SearchTerms {

	/**
	 * @inheritDoc
	 */
	public function getClientData( $snapshots, array $filterData, $limit = 20 ): array {
		// This report is always aggregated
		$filterForUser = $filterData['user'] ?? null;
		if ( !$filterForUser ) {
			return [];
		}
		$processed = [];
		$snapshot = $snapshots[0];
		$data = $snapshot->getData();
		$users = $data['users'];
		foreach ( $users as $username => $data ) {
			if ( $username !== $filterForUser ) {
				continue;
			}
			foreach ( $data as $term => $hits ) {
				$processed[] = [
					'name' => $term,
					'value' => $hits
				];
			}
		}

		return $processed;
	}

	/**
	 * @inheritDoc
	 */
	public function getClientReportHandler(): ClientReportHandler {
		return new ClientReportHandler(
			[ 'ext.bluespice.extendedsearch.statistics' ],
			'bs.extendedsearch.report.SearchTermsByUserReport'
		);
	}
}
