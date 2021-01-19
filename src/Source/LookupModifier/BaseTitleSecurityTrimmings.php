<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class BaseTitleSecurityTrimmings extends Base {
	/** @var \Config */
	protected $config;
	/** @var \Elastica\Search */
	protected $search;

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup &$lookup
	 * @param \IContextSource $context
	 */
	public function __construct( &$lookup, \IContextSource $context ) {
		parent::__construct( $lookup, $context );

		// Should be injected
		$this->config = Backend::instance()->getConfig();
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

	/**
	 *
	 * @return int
	 */
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
	 */
	public function apply() {
		$prepLookup = clone $this->oLookup;

		$size = $this->oLookup->getSize();

		// Prepare preprocessor query
		$prepLookup->setSize( $size );
		$prepLookup->clearSourceField();
		$prepLookup->addSourceField( 'basename' );
		$prepLookup->addSourceField( 'namespace' );
		$prepLookup->addSourceField( 'prefixed_title' );

		$excludes = [];

		$this->getExcludesForCurrentPage( $prepLookup, $size, $excludes );

		if ( empty( $excludes ) ) {
			return;
		}

		// Add result _ids to exclude from the search
		$this->oLookup->addBoolMustNotTerms( '_id', $excludes );
	}

	/**
	 * Runs page-sized queries until there are enought allowed results
	 * to fill a page, or until there are no more results to go over
	 *
	 * @param Lookup $prepLookup
	 * @param int $size
	 * @param array &$excludes
	 */
	protected function getExcludesForCurrentPage( $prepLookup, $size, &$excludes ) {
		$validCount = 0;
		$user = \RequestContext::getMain()->getUser();
		$services = \MediaWiki\MediaWikiServices::getInstance();
		$spFactory = $services->getSpecialPageFactory();
		$permManager = $services->getPermissionManager();

		while ( $validCount < $size ) {
			$results = $this->runPrepQuery( $prepLookup );
			if ( !$results ) {
				// No (more) results can be retrieved
				break;
			}

			foreach ( $results->getResults() as $resultObject ) {
				$data = $resultObject->getData();

				if ( isset( $data['namespace'] ) == false ) {
					// If result has no namespace set, \Title creation is N/A
					// therefore we should allow user to see it
					$validCount++;
					continue;
				}

				if ( isset( $data['prefixed_title'] ) ) {
					$title = \Title::newFromText( $data['prefixed_title'] );
				} else {
					$title = \Title::makeTitle( $data['namespace'], $data['basename'] );
				}
				if ( !$title instanceof \Title ) {
					if ( $title->isContentPage() && $title->exists() == false ) {
						// I cant think of a good reason to show non-existing title in the search
						$excludes[] = $resultObject->getId();
						continue;
					}
				}

				if ( $title->isSpecialPage() ) {
					$sp = $spFactory->getPage( $title->getDBkey() );
					if ( !$sp instanceof \SpecialPage ) {
						$excludes[] = $resultObject->getId();
						continue;
					}
					$isAllowed = $services->getPermissionManager()->userHasRight(
						$user,
						$sp->getRestriction()
					);
					if ( !$isAllowed ) {
						$excludes[] = $resultObject->getId();
						continue;
					}
				}

				if ( $permManager->userCan( 'read', $user, $title ) == false ) {
					$excludes[] = $resultObject->getId();
				}

				$validCount++;
			}

			// Get next page of results from preprocessor lookup
			$prepLookup->setFrom( $prepLookup->getFrom() + $prepLookup->getSize() );
		}
	}

	/**
	 * Runs preprocessor query
	 *
	 * @param Lookup $lookup
	 * @return array|false if no results are retrieved
	 */
	protected function runPrepQuery( $lookup ) {
		try {
			$results = $this->search->search( $lookup->getQueryDSL() );
		} catch ( \RuntimeException $ex ) {
			// If query is invalid, let main query run catch it
			return false;
		}

		$totalCount = $results->getTotalHits();
		if ( $totalCount == 0 ) {
			// No results at all for this query
			return false;
		}

		$pageCount = count( $results->getResults() );
		if ( $pageCount == 0 ) {
			// No results on page
			return false;
		}

		return $results;
	}

	public function undo() {
		$this->oLookup->removeBoolMustNot( '_id' );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [
			Backend::QUERY_TYPE_AUTOCOMPLETE,
			Backend::QUERY_TYPE_SEARCH
		];
	}
}
