<?php

namespace BS\ExtendedSearch\Statistics\SnapshotProvider;

use BlueSpice\ExtendedStatistics\ISnapshotProvider;
use BlueSpice\ExtendedStatistics\Snapshot;
use BlueSpice\ExtendedStatistics\SnapshotDate;
use Wikimedia\Rdbms\LoadBalancer;

class SearchStats implements ISnapshotProvider {
	/** @var LoadBalancer */
	private $loadBalancer;

	/**
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param SnapshotDate $date
	 * @return Snapshot
	 */
	public function generateSnapshot( SnapshotDate $date ): Snapshot {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		$ts = $date->mwDate();
		$res = $db->select(
			[ 'bs_extendedsearch_history', 'user' ],
			[ 'esh_term as term', 'esh_user as user_id', 'user_name', 'COUNT( esh_term ) as hits' ],
			[
				"esh_timestamp LIKE " . $db->addQuotes( "$ts%" ),
			],
			__METHOD__,
			[
				'GROUP BY' => 'esh_term, esh_user'
			],
			[
				'user' => [
					"INNER JOIN", [ 'esh_user=user_id' ]
				]
			]
		);

		$terms = [];
		$perUser = [];
		foreach ( $res as $row ) {
			$term = $row->term;
			$hits = (int)$row->hits;
			if ( !isset( $terms[$term] ) ) {
				$terms[$term] = 0;
			}
			$terms[$term] += $hits;
			if ( !isset( $perUser[$row->user_name] ) ) {
				$perUser[$row->user_name] = [];
			}
			if ( !isset( $perUser[$row->user_name][$term] ) ) {
				$perUser[$row->user_name][$term] = 0;
			}
			$perUser[$row->user_name][$term] += $hits;
		}

		return new Snapshot( $date, $this->getType(), [ 'terms' => $terms, 'users' => $perUser ] );
	}

	/**
	 * @inheritDoc
	 */
	public function aggregate(
		array $snapshots, $interval = Snapshot::INTERVAL_DAY, $date = null
	): Snapshot {
		$terms = [];
		$perUser = [];
		foreach ( $snapshots as $snapshot ) {
			$terms = $this->mergeTerms( $snapshot->getData()['terms'], $terms );
			$perUser = $this->mergePerUser( $snapshot->getData()['users'], $perUser );
		}

		return new Snapshot(
			$date ?? new SnapshotDate(), $this->getType(), [
				'terms' => $terms, 'users' => $perUser
			], $interval
		);
	}

	/**
	 * @param array $new
	 * @param array $base
	 * @return array
	 */
	private function mergeTerms( $new, $base ) {
		foreach ( $new as $term => $hits ) {
			if ( !isset( $base[$term] ) ) {
				$base[$term] = 0;
			}
			$base[$term] += $hits;
		}

		return $base;
	}

	/**
	 * @param array $new
	 * @param array $base
	 * @return array
	 */
	private function mergePerUser( $new, $base ) {
		foreach ( $new as $user => $data ) {
			if ( !isset( $base[$user] ) ) {
				$base[$user] = [];
			}
			foreach ( $data as $term => $hits ) {
				if ( !isset( $base[$user][$term] ) ) {
					$base[$user][$term] = 0;
				}
				$base[$user][$term] += $hits;
			}
		}

		return $base;
	}

	/**
	 * @inheritDoc
	 */
	public function getType() {
		return 'es-searchstats';
	}

	/**
	 * @inheritDoc
	 */
	public function getSecondaryData( Snapshot $snapshot ) {
		return null;
	}
}
