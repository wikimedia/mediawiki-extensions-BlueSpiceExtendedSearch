<?php

namespace BS\ExtendedSearch\Source\QueryProcessor;

class WikiPageNamespaceTextAggregation extends Base {

	/**
	 *
	 * @param \Elastica\Query $oQuery
	 * @param \Elastica\QueryBuilder $oQueryBuilder
	 */
	public function process( &$oQuery, $oQueryBuilder ) {
		$oNamespaceTextAggregation = new \Elastica\Aggregation\Terms( 'all_namespaces' );
		$oNamespaceTextAggregation->setField( 'namespace_text' );

		$oQuery->addAggregation( $oNamespaceTextAggregation );
	}
}