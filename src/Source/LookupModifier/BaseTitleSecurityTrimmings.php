<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\SearchResultSet;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class BaseTitleSecurityTrimmings extends LookupModifier {
	/** @var Backend */
	protected $backend;
	/** @var Config */
	protected $config;

	/**
	 * @param MediaWikiServices $services
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @return LookupModifier
	 */
	public static function factory( MediaWikiServices $services, Lookup $lookup, IContextSource $context ) {
		return new static( $services->getService( 'BSExtendedSearchBackend' ), $lookup, $context );
	}

	/**
	 *
	 * @param Backend $backend
	 * @param Lookup &$lookup
	 * @param IContextSource $context
	 */
	public function __construct( Backend $backend, &$lookup, IContextSource $context ) {
		parent::__construct( $lookup, $context );
		$this->backend = $backend;
		$this->config = $this->backend->getConfig();
	}

	/**
	 * This modifier should be applied last
	 *
	 * @return int
	 */
	public function getPriority() {
		return 999999;
	}

	/**
	 * Filters out titles user is not allowed to read, guaranteeing
	 * there will be enough valid (allowed) results to fill the page -
	 * unless there are not enough valid results for this query to fill the page
	 *
	 * Logically this is LookupModifier, but since it runs query, and needs
	 * resources unavailable to LookupModifier, its implemented here
	 */
	public function apply() {
		$excludes = [];
		$lookup = clone $this->lookup;
		if ( $lookup->getSearchAfter() ) {
			$lookup->setFrom( 0 );
		}
		$lookup->removeForceTerm();
		$this->getExcludesForCurrentPage( $lookup, $excludes );

		if ( empty( $excludes ) ) {
			return;
		}

		// Add result _ids to exclude from the search
		$this->lookup->addBoolMustNotTerms( '_id', $excludes );
	}

	/**
	 * Runs page-sized queries until there are enough allowed results
	 * to fill a page, or until there are no more results to go over
	 *
	 * @param Lookup $prepLookup
	 * @param array &$excludes
	 */
	protected function getExcludesForCurrentPage( $prepLookup, &$excludes ): void {
		$validCount = 0;
		$user = RequestContext::getMain()->getUser();
		$services = \MediaWiki\MediaWikiServices::getInstance();
		$spFactory = $services->getSpecialPageFactory();
		$permManager = $services->getPermissionManager();

		while ( $validCount < (int)$prepLookup->getSize() ) {
			$results = $this->runPrepQuery( $prepLookup );
			if ( !$results ) {
				// No (more) results can be retrieved
				break;
			}

			$searchAfter = [];
			foreach ( $results->getResults() as $resultObject ) {
				$searchAfter = $resultObject->getSort();
				$data = $resultObject->getData();
				if ( $this->backend->isForeignIndex( $resultObject->getIndex() ) ) {
					if ( $resultObject->getType() === 'wikipage' && $data['namespace'] !== NS_FILE ) {
						$excludes[] = $resultObject->getId();
						continue;
					}
					$validCount++;
					continue;
				}

				if ( isset( $data['namespace'] ) == false ) {
					// If result has no namespace set, Title creation is N/A
					// therefore we should allow user to see it
					$validCount++;
					continue;
				}

				if ( isset( $data['prefixed_title'] ) ) {
					$title = Title::newFromText( $data['prefixed_title'] );
				} else {
					$title = Title::makeTitleSafe( $data['namespace'], $data['basename'] );
				}
				if ( !$title instanceof Title ) {
					if ( $title->isContentPage() && $title->exists() == false ) {
						// I cant think of a good reason to show non-existing title in the search
						$excludes[] = $resultObject->getId();
						continue;
					}
				}

				if ( $title->isSpecialPage() ) {
					$sp = $spFactory->getPage( $title->getDBkey() );
					if ( !( $sp instanceof SpecialPage ) ) {

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
				} elseif ( !$permManager->userCan( 'read', $user, $title ) ) {
					$excludes[] = $resultObject->getId();
					continue;
				}

				$validCount++;
			}

			// Get next page of results from preprocessor lookup
			if ( $searchAfter ) {
				$prepLookup->setSearchAfter( $searchAfter );
			}
		}
	}

	/**
	 * Runs preprocessor query
	 *
	 * @param Lookup $lookup
	 * @return SearchResultSet|null
	 */
	protected function runPrepQuery( $lookup ) {
		try {
			$results = $this->backend->runRawQuery( $lookup );
		} catch ( \RuntimeException $ex ) {
			// If query is invalid, let main query run catch it
			return null;
		}

		$totalCount = $results->getTotalHits();
		if ( $totalCount == 0 ) {
			// No results at all for this query
			return null;
		}

		$pageCount = count( $results->getResults() );
		if ( $pageCount == 0 ) {
			// No results on page
			return null;
		}

		return $results;
	}

	public function undo() {
		$this->lookup->removeBoolMustNot( '_id' );
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
