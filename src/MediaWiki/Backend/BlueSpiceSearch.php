<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

use BS\ExtendedSearch\Lookup;
use MediaWiki\MediaWikiServices;
use SearchResult;

class BlueSpiceSearch extends \SearchEngine {
	/** @var \SearchEngine|null */
	protected $fallbackSearchEngine = null;
	/** @var \BS\ExtendedSearch\Backend */
	protected $backend;

	public function __construct() {
		$this->backend = \BS\ExtendedSearch\Backend::instance();
	}

	/**
	 *
	 * @param string $term
	 * @return SearchResultSet
	 */
	public function searchText( $term ) {
		return $this->fullSearchWrapper( $term );
	}

	/**
	 *
	 * @param string $term
	 * @return SearchResultSet
	 */
	public function searchTitle( $term ) {
		return $this->fullSearchWrapper( $term, [ 'basename' ] );
	}

	/**
	 * @param string $term
	 * @param array|null $sourceFields
	 * @return SearchResultSet
	 */
	protected function fullSearchWrapper( $term, $sourceFields = [] ) {
		$term = trim( $term );
		$results = $this->runFullSearch( $term, $sourceFields );

		$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
		foreach ( $results as $title ) {
			$searchResultSet->add(
				SearchResult::newFromTitle( $title, $searchResultSet )
			);
		}

		return $searchResultSet;
	}

	/**
	 *
	 * @param string $search
	 * @return \SearchSuggestionSet
	 */
	protected function completionSearchBackend( $search ) {
		$results = $this->runNGramSearch( trim( $search ) );
		return \SearchSuggestionSet::fromTitles( $results );
	}

	/**
	 *
	 * @param string $search
	 * @return \Title[]
	 */
	protected function runNGramSearch( $search ) {
		if ( $search === '' ) {
			return [];
		}

		$acConfig = $this->backend->getAutocompleteConfig();
		$suggestField = $acConfig['SuggestField'];

		$lookup = $this->getLookup();
		$lookup->setBoolMatchQueryString( $suggestField, $search );

		$search = new \Elastica\Search( $this->backend->getClient() );
		$search->addIndex( $this->backend->getConfig()->get( 'index' ) . '_wikipage' );

		$results = $search->search( $lookup->getQueryDSL() );

		$titles = [];
		foreach ( $results->getResults() as $item ) {
			$data = $item->getData();
			$title = \Title::newFromText( $data['prefixed_title'] );
			if ( $title instanceof \Title ) {
				$titles[] = $title;
			}
		}
		return $titles;
	}

	/**
	 * @param string $search
	 * @param array|null $sourceFields
	 * @return \Title[]
	 */
	protected function runFullSearch( $search, $sourceFields = [] ) {
		if ( $search === '' ) {
			return [];
		}

		$lookup = $this->getLookup();
		$qs = [
			'query' => $search,
			'default_operator' => 'AND',
		];
		if ( is_array( $sourceFields ) && !empty( $sourceFields ) ) {
			$qs['fields'] = $sourceFields;
		}
		$lookup->setQueryString( $qs );

		$resultSet = $this->backend->runLookup( $lookup );
		if ( property_exists( $resultSet, 'exception' ) ) {
			return [];
		}

		$titles = [];
		foreach ( $resultSet->results as $item ) {
			if ( !isset( $item['prefixed_title'] ) || !isset( $item['namespace' ] ) ) {
				continue;
			}
			$title = \Title::newFromText( $item['prefixed_title'] );
			if (
				$search !== '*' &&
				strpos( strtolower( $title->getText() ), strtolower( $search ) ) === false
			) {
				continue;
			}

			if ( $title instanceof \Title ) {
				$titles[] = $title;
			}
		}

		return $titles;
	}

	/**
	 *
	 * @param string $term
	 * @return bool
	 */
	protected function searchContainedSyntax( $term ) {
		return false;
	}

	/**
	 *
	 * @param int $id
	 * @param string $title
	 * @param string $text
	 */
	public function update( $id, $title, $text ) {
		$this->getFallbackSearchEngine()->update( $id, $title, $text );
	}

	/**
	 *
	 * @param int $id
	 * @param string $title
	 */
	public function updateTitle( $id, $title ) {
		$this->getFallbackSearchEngine()->updateTitle( $id, $title );
	}

	/**
	 *
	 * @param int $id
	 * @param string $title
	 */
	public function delete( $id, $title ) {
		$this->getFallbackSearchEngine()->delete( $id, $title );
	}

	/**
	 * Gets default search engine based on DB type
	 *
	 * @return \SearchEngine
	 */
	protected function getFallbackSearchEngine() {
		if ( $this->fallbackSearchEngine === null ) {
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
			$class = \BS\ExtendedSearch\Setup::getSearchEngineClass( $lb );
			$this->fallbackSearchEngine = new $class( $lb );
		}
		return $this->fallbackSearchEngine;
	}

	/**
	 * @return Lookup
	 */
	protected function getLookup() {
		$lookup = new \BS\ExtendedSearch\Lookup();
		if ( !empty( $this->namespaces ) ) {
			$lookup->addTermsFilter( 'namespace', $this->namespaces );
		}

		$lookup->setSize( $this->limit );
		$lookup->setFrom( $this->offset );
		$lookup->addTermFilter( '_type', 'wikipage' );
		$lookup->addSort( '_score', Lookup::SORT_DESC );

		return $lookup;
	}
}
