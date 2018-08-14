<?php

namespace BS\ExtendedSearch\Data\TagCloud\Searchstats;

use \BlueSpice\Data\IPrimaryDataProvider;
use BlueSpice\Data\FilterFinder;
use BlueSpice\Data\Filter;
use BlueSpice\Data\Filter\StringValue;
use BlueSpice\Data\Filter\ListValue;
use BlueSpice\Data\Filter\Numeric;
use BlueSpice\TagCloud\Data\TagCloud\Schema;
use BlueSpice\TagCloud\Data\TagCloud\Record;
use BlueSpice\TagCloud\Context;

class PrimaryDataProvider implements IPrimaryDataProvider {

	/**
	 *
	 * @var Record[]
	 */
	protected $data = [];

	/**
	 *
	 * @var \Wikimedia\Rdbms\IDatabase
	 */
	protected $db = null;

	/**
	 *
	 * @var Context
	 */
	protected $context = null;

	/**
	 *
	 * @param \Wikimedia\Rdbms\IDatabase $db
	 */
	public function __construct( $db, Context $context ) {
		$this->db = $db;
		$this->context = $context;
	}

	/**
	 *
	 * @param \BlueSpice\Data\ReaderParams $params
	 */
	public function makeData( $params ) {
		$this->data = [];
		try{
			$res = $this->db->select(
				'bs_extendedsearch_history',
				[ Record::NAME => 'esh_term', Record::COUNT => 'esh_hits' ],
				$this->makePreFilterConds( $params ),
				__METHOD__,
				$this->makePreOptionConds( $params )
			);
		} catch( \Exception $e ) {
			wfDebugLog( 'BSExtendedSearch',  'Error in query: ' . $this->db->lastQuery() );
		}

		foreach( $res as $row ) {
			if( count( $this->data ) >= $params->getLimit() ) {
				break;
			}
			$this->appendRowToData( $row );
		}

		return $this->data;
	}

	/**
	 *
	 * @param \BlueSpice\Data\ReaderParams $params
	 * @return array
	 */
	protected function makePreFilterConds( $params ) {
		$conds = [];
		$schema = new Schema();
		$fields = array_values( $schema->getFilterableFields() );
		$filterFinder = new FilterFinder( $params->getFilter() );
		foreach( $fields as $fieldName ) {
			$filter = $filterFinder->findByField( $fieldName );
			if( !$filter instanceof Filter ) {
				continue;
			}
			if( $filter instanceof ListValue ) {
				$values = implode( "','", $filter->getValue() );
				$name = $this->aliasToFieldName( $fieldName );
				if( $filter->getComparison() === ListValue::COMPARISON_CONTAINS ) {
					$conds[$name] = $fieldName;
					$filter->setApplied();
					continue;
				}
				if( $filter->getComparison() === ListValue::COMPARISON_NOT_CONTAINS ) {
					$conds[] = "$name NOT IN ('$values')";
					$filter->setApplied();
					continue;
				}

			}
			switch ( $filter->getComparison() ) {
				case Numeric::COMPARISON_GREATER_THAN:
					$conds[] = "$fieldName > {$filter->getValue()}";
					break;
				case Numeric::COMPARISON_LOWER_THAN:
					$conds[] = "$fieldName < {$filter->getValue()}";
					break;
				case StringValue::COMPARISON_CONTAINS:
					$conds[] = $this->db->buildLike(
						$this->db->anyString(),
						$fieldName,
						$this->db->anyString()
					);
					break;
				case StringValue::COMPARISON_NOT_EQUALS:
				case Numeric::COMPARISON_NOT_EQUALS:
					$conds[] = "$fieldName != {$filter->getValue()}";
					break;
				case StringValue::COMPARISON_EQUALS:
				case Numeric::COMPARISON_EQUALS:
				default:
					$conds[$fieldName] = $filter->getValue();
			}
			$filter->setApplied();
		}

		return $conds;
	}

	/**
	 *
	 * @param \BlueSpice\Data\ReaderParams $params
	 * @return array
	 */
	protected function makePreOptionConds( $params ) {
		$conds = [
			'GROUP BY' => 'esh_term',
			//'LIMIT' => $params->getLimit(),
			'ORDER BY' => 'esh_hits DESC'
		];

		return $conds;
	}

	protected function appendRowToData( $row ) {
		$this->data[] = new Record( (object) [
			Record::NAME => $this->normalizeTerm( $row->{Record::NAME} ),
			Record::COUNT => (int)$row->{Record::COUNT},
			Record::LINK => '',
		] );
	}

	protected function normalizeTerm( $term ) {
		$term = preg_replace( "/(\\\)/", "", $term ); //'term\\.com' -> 'term.com'
		$term = preg_replace( "/(\*)/", "", $term ); //'*term*' -> 'term'
		$term = preg_replace( "/(~.*)/", "", $term ); //'term~0.5' -> 'term'
		$term = preg_replace( "/(\"*)/", "", $term ); //'"term"' -> 'term'
		$term = preg_replace( "/(\%20*)/", " ", $term); //'term1%20term2' -> 'term1 term2'
		$term = preg_replace( "/(\%c3%b6*)/i", "ö", $term); //'sch%c3%b6n' -> 'schön'
		$term = preg_replace( "/(\%c3%96*)/i", "Ö", $term); //'sch%c3%b6n' -> 'schön'
		$term = preg_replace( "/(\%c3%bc*)/i", "ü", $term); //'t%c3%bcr' -> 'tür'
		$term = preg_replace( "/(\%c3%9c*)/i", "Ü", $term); //'t%c3%bcr' -> 'tür'
		$term = preg_replace( "/(\%c3%a4*)/i", "ä", $term); //'b%c3%a4r' -> 'bär'
		$term = preg_replace( "/(\%c3%84*)/i", "Ä", $term); //'b%c3%a4r' -> 'bär'
		$term = preg_replace( "/(\%c3%9F*)/i", "ß", $term); //'spa%c3%9F' -> 'spaß'

		$term = trim( $term ); //' term  ' -> 'term'

		return $term;
	}

	/**
	 * cause of mysql alias resons -.-
	 * @param type $alias
	 */
	protected function aliasToFieldName( $alias ) {
		switch ( $alias ) {
			case Record::NAME:
				$alias = 'esh_term';
				break;
			case Record::COUNT:
				$alias = 'esh_hits';
				break;
			default:
		}
		return $alias;
	}
}
