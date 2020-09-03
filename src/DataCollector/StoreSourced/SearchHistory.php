<?php

namespace BS\ExtendedSearch\DataCollector\StoreSourced;

use BlueSpice\Data\FieldType;
use BlueSpice\Data\Filter;
use BlueSpice\Data\Filter\Date;
use BlueSpice\Data\IRecord;
use BlueSpice\Data\IStore;
use BlueSpice\Data\Sort;
use BlueSpice\EntityFactory;
use BlueSpice\ExtendedStatistics\DataCollector\StoreSourced;
use BlueSpice\ExtendedStatistics\Entity\Snapshot;
use BlueSpice\ExtendedStatistics\Util\SnapshotRange\Daily;
use BlueSpice\Timestamp;
use BS\ExtendedSearch\Data\SearchHistory\Record;
use BS\ExtendedSearch\Data\SearchHistory\Store;
use BS\ExtendedSearch\Entity\Collection\SearchHistory as Collection;
use Config;
use MediaWiki\MediaWikiServices;

class SearchHistory extends StoreSourced {

	/**
	 *
	 * @param string $type
	 * @param MediaWikiServices $services
	 * @param Snapshot $snapshot
	 * @param Config|null $config
	 * @param EntityFactory|null $factory
	 * @param IStore|null $store
	 * @return DataCollector
	 */
	public static function factory( $type, MediaWikiServices $services, Snapshot $snapshot,
		Config $config = null, EntityFactory $factory = null, IStore $store = null ) {
		if ( !$config ) {
			$config = $snapshot->getConfig();
		}
		if ( !$factory ) {
			$factory = $services->getService( 'BSEntityFactory' );
		}
		if ( !$store ) {
			$store = new Store();
		}
		return new static( $type, $snapshot, $config, $factory, $store );
	}

	/**
	 *
	 * @return array
	 */
	protected function getFilter() {
		$ts = new Timestamp( $this->snapshot->get( Snapshot::ATTR_TIMESTAMP_CREATED ) );
		$range = new Daily( $ts );
		return array_merge( parent::getFilter(), [
			(object)[
				Filter::KEY_COMPARISON => Date::COMPARISON_LOWER_THAN,
				Filter::KEY_PROPERTY => Record::TIMESTAMP,
				Filter::KEY_VALUE => $range->getStart()->getTimestamp( TS_MW ),
				Filter::KEY_TYPE => FieldType::DATE
			],
			(object)[
				Filter::KEY_COMPARISON => Date::COMPARISON_GREATER_THAN,
				Filter::KEY_PROPERTY => Record::TIMESTAMP,
				Filter::KEY_VALUE => $range->getEnd()->getTimestamp( TS_MW ),
				Filter::KEY_TYPE => FieldType::DATE
			],
		] );
	}

	/**
	 *
	 * @return array
	 */
	protected function getSort() {
		return [ (object)[
			Sort::KEY_PROPERTY => Record::TIMESTAMP,
			Sort::KEY_DIRECTION => Sort::ASCENDING
		] ];
	}

	/**
	 *
	 * @param IRecord $record
	 * @return \stdClass
	 */
	protected function map( IRecord $record ) {
		return (object)[
			Collection::ATTR_TYPE => Collection::TYPE,
			Collection::ATTR_NUMBER_SEARCHED => 1,
			Collection::ATTR_TIMESTAMP_CREATED => $record->get( Record::TIMESTAMP ),
			Collection::ATTR_TERM => $record->get( Record::TERM )
		];
	}
}
