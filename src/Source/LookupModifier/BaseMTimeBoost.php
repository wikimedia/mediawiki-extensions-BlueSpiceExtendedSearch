<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;

class BaseMTimeBoost extends LookupModifier {

	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 * @param IContextSource $context
	 */
	public function __construct( $lookup, IContextSource $context ) {
		parent::__construct( $lookup, $context );

		$this->backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
	}

	/**
	 *
	 * @return int
	 */
	public function getPriority() {
		return 100;
	}

	public function apply() {
		if ( !$this->lookup ) {
			return;
		}
		$prepLookup = new Lookup( $this->lookup->getQueryDSL() );

		$prepLookup->clearSourceField();
		$prepLookup->setSort( [ 'mtime' => Lookup::SORT_DESC ] );
		$prepLookup->removeSearchAfter();
		$prepLookup->removeForceTerm();
		$prepLookup->addSourceField( [ 'mtime' ] );
		$ids = $this->runPrepQuery( $prepLookup );

		$this->lookup->addShouldTerms( '_id', $ids, 2, false );
	}

	/**
	 * Runs preprocessor query
	 *
	 * @param Lookup $lookup
	 * @return array
	 */
	protected function runPrepQuery( $lookup ): array {
		try {
			$results = $this->backend->runRawQuery( $lookup );
		} catch ( \RuntimeException $ex ) {
			// If query is invalid, let main query run catch it
			return [];
		}

		$totalCount = $results->getTotalHits();
		if ( $totalCount == 0 ) {
			// No results at all for this query
			return [];
		}

		return array_map( static function ( $result ) {
			return $result->getId();
		}, $results->getResults() );
	}

	public function undo() {
		$this->lookup->removeShouldTerms( '_id' );
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
