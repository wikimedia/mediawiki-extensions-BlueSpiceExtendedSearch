<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseTitleSecurityTrimmings extends Base {
	protected $config;
	protected $search;

	public function __construct( &$lookup, \IContextSource $context ) {
		parent::__construct( $lookup, $context );

		//Should be injected
		$this->config = \BS\ExtendedSearch\Backend::instance( 'local' )->getConfig();
		$this->setSearch();
	}

	public function setSearch() {
		$client = new \Elastica\Client(
			$this->config->get( 'connection' )
		);
		$search = new \Elastica\Search( $client );
		$search->addIndex( $this->config->get( 'index' ) . '_*' );

		$this->search = $search;
	}

	public function getPriority() {
		return 100;
	}

	/**
	 * Filters out titles user is not allowed to read, guaranteeing
	 * there will be enough valid (allowed) results to fill the page -
	 * unless there are not enough valid results for this query to fill the page
	 *
	 * Logically this is LookupModifier, but since it runs query, and needs
	 * resources unavaialable to LookupModifier, its implemented here
	 *
	 * @param Lookup $lookup
	 * @param \Elastica\Search $search
	 */
	public function apply() {
		$prepLookup = clone $this->oLookup;

		$size = $this->oLookup->getSize();

		//Prepare preprocessor query
		$prepLookup->setSize( $size );
		$prepLookup->clearSourceField();
		$prepLookup->addSourceField( 'basename' );
		$prepLookup->addSourceField( 'namespace' );

		$excludes = [];

		$this->getExcludesForCurrentPage( $prepLookup, $size, $excludes );

		if( empty( $excludes ) ) {
			return;
		}

		//Add result _ids to exclude from the search
		$this->oLookup->addBoolMustNotTerms( '_id', $excludes );
	}

	/**
	 * Runs page-sized queries until there are enought allowed results
	 * to fill a page, or until there are no more results to go over
	 *
	 * @param type $prepLookup
	 * @param type $search
	 * @param type $excludes
	 */
	protected function getExcludesForCurrentPage( $prepLookup, $size, &$excludes ) {
		$validCount = 0;

		while( $validCount < $size ) {
			$results = $this->runPrepQuery( $prepLookup );
			if( !$results ) {
				//No (more) results can be retieved
				break;
			}

			foreach( $results->getResults() as $resultObject ) {
				$data = $resultObject->getData();

				if( isset( $data['namespace'] ) == false ) {
					//If result has no namespace set, \Title creation is N/A
					//therefore we should allow user to see it
					$validCount++;
					continue;
				}

				$title = \Title::makeTitle( $data['namespace'], $data['basename'] );
				if( !$title instanceof \Title ) {
					if( $title->isContentPage() && $title->exists() == false ) {
						//I cant think of a good reason to show non-existing title in the search
						$excludes[] = $resultObject->getId();
						continue;
					}
				}

				if( $title->userCan( 'read' ) == false ) {
					$excludes[] = $resultObject->getId();
				}

				$validCount++;
			}

			//Get next page of results from preprocessor lookup
			$prepLookup->setFrom( $prepLookup->getFrom() + $prepLookup->getSize() );
		}
	}

	/**
	 * Runs preprocessor query
	 *
	 * @param Lookup $lookup
	 * @param \Elastica\Search $search
	 * @return array|false if no results are retrieved
	 */
	protected function runPrepQuery( $lookup ) {
		try {
			$results = $this->search->search( $lookup->getQueryDSL() );
		} catch( \RuntimeException $ex ) {
			//If query is invalid, let main query run catch it
			return false;
		}

		$totalCount = $results->getTotalHits();
		if( $totalCount == 0 ) {
			//No results at all for this query
			return false;
		}

		$pageCount = count( $results->getResults() );
		if( $pageCount == 0 ) {
			//No results on page
			return false;
		}

		return $results;
	}

	public function undo() {
		$this->oLookup->removeBoolMustNot( '_id' );
	}

}
