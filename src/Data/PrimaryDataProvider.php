<?php

namespace BS\ExtendedSearch\Data;

use Exception;
use FormatJson;
use Elastica\Client;
use Elastica\Index;
use Elastica\Search;
use BS\ExtendedSearch\Backend;
use BlueSpice\Data\Schema;
use BlueSpice\Data\Record;
use BlueSpice\Data\FieldType;
use BlueSpice\Data\IPrimaryDataProvider;
use BlueSpice\Data\FilterFinder;
use BlueSpice\Data\ReaderParams;
use BlueSpice\Data\Filter\ListValue;
use BlueSpice\Data\Filter\Boolean;
use BlueSpice\Data\Filter\Numeric;
use BlueSpice\Data\Filter\Date;

abstract class PrimaryDataProvider implements IPrimaryDataProvider {
	const TYPE = 'type';

	const TEXT = 'text';
	const KEYWORD = 'keyword';
	const INTEGER = 'integer';
	const BOOLEAN = 'boolean';
	const DATE = 'date';
	const FLOAT = 'float';

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
	 *
	 * @return Client
	 */
	public function getSearchClient() {
		return $this->getSearchBackend()->getClient();
	}

	/**
	 *
	 * @return Index
	 */
	public function getSearchIndex() {
		return $this->getSearchBackend()->getIndexByType( $this->getIndexType() );
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
	 * @return Search
	 */
	protected function getSearch() {
		$search = new Search( $this->getSearchClient() );
		$search->addIndex( $this->getSearchIndex() );
		$search->addType(
			new \Elastica\Type( $this->getSearchIndex(), $this->getIndexType() )
		);
		return $search;
	}

	/**
	 *
	 * @param ReaderParams $params
	 * @return Record[]
	 */
	public function makeData( $params ) {
		$query = [];
		$query = $this->makePreFilterConds( $params, $query );
		$query = $this->makePreOptionConds( $params, $query );
		$queryJSON = FormatJson::encode( $query, true );

		wfDebugLog(
			"BlueSpiceExtendedSearch-{$this->getTypeName()}",
			__METHOD__ . ":\n" . $queryJSON
		);
		do {
			try {
				$result = $this->getSearch()->search( $query );
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

			if ( $result->count() < 1 ) {
				return $this->data;
			}
			foreach ( $result as $row ) {
				$this->appendRowToData( $row );
				if ( $params->getLimit() === $params::LIMIT_INFINITE ) {
					continue;
				}
				if ( count( $this->data ) >= $params->getLimit() ) {
					return $this->data;
				}
			}
			// because elastic search cant handle from + size larger than
			// 10000 -.-
			// see: https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-search-after.html
			$query['from'] = -1;
			$query['search_after'] = $row->getHit()['sort'];
		} while ( true );

		return $this->data;
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
	 * @param \Elastica\Result $row
	 * @return null
	 */
	abstract protected function appendRowToData( \Elastica\Result $row );

}
