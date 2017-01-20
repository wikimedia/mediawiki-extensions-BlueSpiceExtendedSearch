<?php

namespace BS\ExtendedSearch\Source\QueryProcessor;

class Base {
	
	/**
	 *
	 * @var \IContextSource
	 */
	protected $oContext = null;
	
	/**
	 * 
	 * @param \IContextSource $oContext
	 */
	public function __construct( $oContext ) {
		$this->oContext = $oContext;
	}

	/**
	 *
	 * @param \Elastica\Query $oQuery
	 * @param \Elastica\QueryBuilder $oQueryBuilder
	 */
	public function process( &$oQuery, $oQueryBuilder ) {
		//Stub
	}
}