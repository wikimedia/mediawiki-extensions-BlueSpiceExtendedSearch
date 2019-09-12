<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

use BS\ExtendedSearch\Lookup;
use Elastica\Client;
use Elastica\Result;
use Elastica\ResultSet;
use Elastica\Search;
use Config;

class BaseMTimeBoost extends Base {

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Search
	 */
	protected $search;

	public function __construct( &$lookup, \IContextSource $context ) {
		parent::__construct( $lookup, $context );

		$this->config = Backend::instance()->getConfig();
		$this->setSearch();
	}

	public function setSearch() {
		$client = new Client(
			$this->config->get( 'connection' )
		);
		$search = new \Elastica\Search( $client );
		$search->addIndex( $this->config->get( 'index' ) . '_*' );

		$this->search = $search;
	}

	public function getPriority() {
		return 100;
	}

	public function apply() {
		$prepLookup = clone $this->oLookup;

		$prepLookup->clearSourceField();
		$prepLookup->addSourceField( 'mtime' );
		$prepLookup->setSort( [ 'mtime' => Lookup::SORT_DESC ] );

		$results = $this->runPrepQuery( $prepLookup );

		$ids = [];
		/** @var Result $result */
		foreach ( $results as $result ) {
			$ids[] = $result->getId();
		}

		$this->oLookup->addShouldTerms( '_id', $ids, 2, false );
	}

	/**
	 * Runs preprocessor query
	 *
	 * @param Lookup $lookup
	 * @return ResultSet
	 */
	protected function runPrepQuery( $lookup ) {
		try {
			$results = $this->search->search( $lookup->getQueryDSL() );
		} catch ( \RuntimeException $ex ) {
			// If query is invalid, let main query run catch it
			return [];
		}

		$totalCount = $results->getTotalHits();
		if ( $totalCount == 0 ) {
			// No results at all for this query
			return [];
		}

		return $results;
	}

	public function undo() {
		$this->oLookup->removeShouldTerms( '_id' );
	}

}
