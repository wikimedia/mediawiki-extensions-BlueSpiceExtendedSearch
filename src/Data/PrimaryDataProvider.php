<?php

namespace BS\ExtendedSearch\Data;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\SearchResult;
use Exception;
use MediaWiki\Json\FormatJson;
use MWStake\MediaWiki\Component\DataStore\FieldType;
use MWStake\MediaWiki\Component\DataStore\Filter\Boolean;
use MWStake\MediaWiki\Component\DataStore\Filter\Date;
use MWStake\MediaWiki\Component\DataStore\Filter\ListValue;
use MWStake\MediaWiki\Component\DataStore\Filter\Numeric;
use MWStake\MediaWiki\Component\DataStore\FilterFinder;
use MWStake\MediaWiki\Component\DataStore\IPrimaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\Record;
use MWStake\MediaWiki\Component\DataStore\Schema;

abstract class PrimaryDataProvider implements IPrimaryDataProvider {
	public const TYPE = 'type';

	public const TEXT = 'text';
	public const KEYWORD = 'keyword';
	public const INTEGER = 'integer';
	public const BOOLEAN = 'boolean';
	public const DATE = 'date';
	public const FLOAT = 'float';

	/**
	 *
	 * @var Record[]
	 */
	protected $data = [];

	/**
	 *
	 * @var Backend
	 */
	protected $searchBackend = null;

	/**
	 *
	 * @var Schema
	 */
	protected $schema = null;

	/**
	 *
	 * @param Backend $searchBackend
	 * @param Schema $schema
	 */
	public function __construct( Backend $searchBackend, Schema $schema ) {
		$this->searchBackend = $searchBackend;
		$this->schema = $schema;
	}

	/**
	 *
	 * @return array
	 */
	public function getValueTypeMapping() {
		return [
			FieldType::STRING => static::KEYWORD,
			FieldType::INT => static::INTEGER,
			FieldType::BOOLEAN => static::BOOLEAN,
			FieldType::DATE => static::DATE,
			FieldType::FLOAT => static::FLOAT,
			FieldType::TEXT => static::TEXT,
			FieldType::LISTVALUE => static::KEYWORD,
		];
	}

	/**
	 *
	 * @return Backend
	 */
	public function getSearchBackend() {
		return $this->searchBackend;
	}

	/**
	 * @return string
	 */
	abstract protected function getIndexType();

	/**
	 * @return string
	 */
	abstract protected function getTypeName();

	/**
	 *
	 * @param ReaderParams $params
	 * @return Record[]
	 */
	public function makeData( $params ) {
		$query = [];
		$query = $this->makePreFilterConds( $params, $query );
		$query = $this->makePreOptionConds( $params, $query );
		$query['_source'] = true;

		do {
			$queryJSON = FormatJson::encode( $query, true );
			try {
				$result = $this->searchBackend->runRawQueryFromData( [
					'index' => implode( ',', $this->searchBackend->getAllIndicesForQuery(
						[ $this->getIndexType() ]
					) ),
					'body' => $queryJSON
				] );
			} catch ( Exception $ex ) {
				// When there is no document in the index yet, a query may
				// crash with "Fielddata access on the _id field is disallowed"
				wfDebugLog(
					"BlueSpiceExtendedSearch-{$this->getTypeName()}",
					__METHOD__ . ":\nException during search - "
						. $ex->getMessage()
				);
				return $this->data;
			}

			if ( $result->getTotalHits() < 1 ) {
				return $this->data;
			}
			$lastRow = null;
			foreach ( $result->getResults() as $row ) {
				$this->appendRowToData( $row );
				$lastRow = $row;
				if ( $params->getLimit() === $params::LIMIT_INFINITE ) {
					continue;
				}
				if ( count( $this->data ) >= $params->getLimit() ) {
					return $this->data;
				}
			}
			// Because OS search can't handle from + size larger than 10000
			// see: https://opensearch.org/docs/2.11/search-plugins/searching-data/paginate/#scroll-search
			$searchAfter = $lastRow ? $lastRow->getParam( 'sort' ) : '';
			if ( is_array( $searchAfter ) ) {
				$firstKey = array_key_first( $searchAfter );
				$searchAfter = $searchAfter[$firstKey];
			}
			$query['from'] = -1;
			$query['search_after'] = $searchAfter;

		} while ( true );
	}

	/**
	 *
	 * @param ReaderParams $params
	 * @param array $query
	 * @return array
	 */
	protected function makePreFilterConds( $params, $query ) {
		if ( empty( (array)$this->schema ) ) {
			return $query;
		}
		$filterFinder = new FilterFinder( $params->getFilter() );
		foreach ( $this->schema->getFilterableFields() as $fieldname ) {
			$filter = $filterFinder->findByField( $fieldname );
			if ( !$filter ) {
				continue;
			}

			$value = $filter->getValue();

			if ( $filter instanceof Numeric ) {
				$value = empty( $value ) ? 0 : (int)$value;
			}

			if ( $filter instanceof Boolean ) {
				$value = empty( $value ) ? false : true;
			}

			if ( $filter instanceof ListValue ) {
				if ( is_object( $value ) ) {
					$value = (array)$value;
				}
				if ( is_string( $value ) ) {
					$value = explode( '', $value );
				}
				if ( !is_array( $value ) ) {
					$value = (array)$value;
				}
				$query['query']['bool']["filter"][] = [
					"terms" => [
						"{$this->getTypeName()}.$fieldname" => array_values( $value )
					]
				];
				$filter->setApplied();
				continue;
			}

			if ( $filter instanceof Date
				&& $filter->getComparison() === Date::COMPARISON_LOWER_THAN ) {
				$query['query']['bool']['filter'][] = [
					"range" => [
						"{$this->getTypeName()}.$fieldname" => [
							"format" => "yyyyMMddHHmmss",
							"lt" => $value,
						]
					]
				];
				continue;
			}
			if ( $filter instanceof Date
				&& $filter->getComparison() === Date::COMPARISON_GREATER_THAN ) {
				$query['query']['bool']['filter'][] = [
					"range" => [
						"{$this->getTypeName()}.$fieldname" => [
							"format" => "yyyyMMddHHmmss",
							"gt" => $value,
						]
					]
				];
				continue;
			}
			if ( $filter instanceof Numeric
				&& $filter->getComparison() === Numeric::COMPARISON_LOWER_THAN ) {
				$query['query']['bool']['filter'][] = [
					"range" => [
						"{$this->getTypeName()}.$fieldname" => [
							"lt" => $value,
						]
					]
				];
				continue;
			}
			if ( $filter instanceof Numeric
				&& $filter->getComparison() === Numeric::COMPARISON_GREATER_THAN ) {
				$query['query']['bool']['filter'][] = [
					"range" => [
						"{$this->getTypeName()}.$fieldname" => [
							"gt" => $value,
						]
					]
				];
				continue;
			}

			$query['query']['bool']["filter"][] = [
				"term" => [
					"{$this->getTypeName()}.$fieldname" => $value
				]
			];

			$filter->setApplied();
		}

		return $query;
	}

	/**
	 *
	 * @param ReaderParams $params
	 * @param array $query
	 * @return array
	 */
	protected function makePreOptionConds( $params, $query ) {
		$sort = $params->getSort();
		if ( is_array( $sort ) ) {
			if ( !isset( $sort[0] ) ) {
				$query['sort']["_id"] = [
					"order" => "desc"
				];
				return $query;
			}
			$sort = $sort[0];
		}
		$mapping = $this->getValueTypeMapping();

		if ( empty( (array)$this->schema ) ) {
			$type = "_id";
		} else {
			$type = $this->schema[$sort->getProperty()][Schema::TYPE];
		}
		if ( isset( $mapping[$type] ) ) {
			$type = $mapping[$type];
		}

		$query['sort'] = [
			"{$this->getTypeName()}." . $sort->getProperty() => [
				"order" => $sort->getDirection(),
				"unmapped_type" => $type,
				// "missing" => "_last"
			]
		];
		if ( $type !== "_id" ) {
			$query['sort']["_id"] = [
				"order" => "desc"
			];
		}
		$query['size'] = $params->getLimit();
		$query["from"] = $params->getStart();

		return $query;
	}

	/**
	 *
	 * @param SearchResult $row
	 * @return null
	 */
	abstract protected function appendRowToData( SearchResult $row );

}
