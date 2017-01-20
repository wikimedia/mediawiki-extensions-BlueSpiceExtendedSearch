<?php

namespace BS\ExtendedSearch;

class Lookup {

	/**
	 *
	 * @var Backend
	 */
	protected $backed = null;

	/**
	 *
	 * @var \IContextSource
	 */
	protected $context = null;

	public function __construct( $oBackend, $oContext = null ) {
		$this->backed = $oBackend;
		if( $oContext instanceof \IContextSource === false ) {
			$oContext = \RequestContext::getMain();
		}
		$this->context = $oContext;
	}

	/**
	 *
	 * @var \Elastica\Search
	 */
	protected $search = null;

	/**
	 *
	 * @var \Elastica\QueryBuilder
	 */
	protected $queryBuilder = null;

	/**
	 *
	 * @var \Elastica\Query
	 */
	protected $query = null;

	/**
	 *
	 * @var string
	 */
	protected $term = '';

	/**
	 *
	 * @var array
	 */
	protected $params = [];

	/**
	 *
	 * @param string $sTerm
	 * @param array $aParams
	 * @return \Elastica\ResultSet
	 */
	public function run( $sTerm, $aParams = [] ) {
		$this->term = $sTerm;
		$this->params = $aParams;

		$this->init();
		$this->setDefaultAggregations();
		$this->applyQueryModifications();

		wfDebugLog(
			'BSExtendedSearch',
			"User {$this->context->getUser()->getName()} queries:\n"
				. \FormatJson::encode( $this->query->toArray(), true )
		);

		return $this->search->search( $this->query );
	}

	protected function init() {
		$this->search = new \Elastica\Search( $this->backed->getClient() );
		$this->queryBuilder = new \Elastica\QueryBuilder();
		$this->query = new \Elastica\Query();

		$this->query->setQuery(
			$this->queryBuilder->query()->query_string( $this->term )
		);
	}

	protected function setDefaultAggregations() {
		$oTypeAggregation = new \Elastica\Aggregation\Terms( 'all_types' );
		$oTypeAggregation->setField( '_type' );

		$oExtensionAggregation = new \Elastica\Aggregation\Terms( 'all_extensions' );
		$oExtensionAggregation->setField( 'extension' );
		
		$oTypeAggregation->addAggregation( $oExtensionAggregation );

		$oTagsAggregation = new \Elastica\Aggregation\Terms( 'all_tags' );
		$oTagsAggregation->setField( 'tags' );

		$oTypeAggregation->addAggregation( $oTagsAggregation );

		$this->query->addAggregation( $oTypeAggregation );
	}

	protected function applyQueryModifications() {
		$aSources = $this->backed->getSources();
		foreach( $aSources as $sSourceKey => $oSource ) {
			$aQPs = $oSource->getQueryProcessors( $this->context );
			foreach( $aQPs as $sOpKey => $oOP ) {
				$oOP->process( $this->query, $this->queryBuilder );
			}
		}
	}
}