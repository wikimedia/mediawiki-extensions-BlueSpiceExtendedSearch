<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

use BS\ExtendedSearch\Lookup;
use BlueSpice\Services;

class BlueSpiceSearch extends \SearchEngine {
	protected $fallbackSearchEngine = null;
	protected $backend;

	public function __construct() {
		$this->backend = \BS\ExtendedSearch\Backend::instance();
	}

	/**
	 *
	 * @param string $term
	 * @return \BS\ExtendedSearch\MediaWiki\Backend\SearchResultSet
	 */
	public function searchText( $term ) {
		$term = trim( $term );
		$results = $this->runFullSearch( 'source_content', $term );

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
	 * @param string $term
	 * @return \BS\ExtendedSearch\MediaWiki\Backend\SearchResultSet
	 */
	public function searchTitle( $term ) {
		$term = trim( $term );
		$results = $this->runFullSearch( 'basename', $term );

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

		$lookup = new \BS\ExtendedSearch\Lookup();
		$lookup->setBoolMatchQueryString( $suggestField, $search );
		$lookup->addTermsFilter( 'namespace', $this->namespaces );
		$lookup->addSourceField( 'prefixed_title' );
		$lookup->setSize( $this->limit );
		$lookup->setFrom( $this->offset );
		$lookup->addSort( '_score', Lookup::SORT_DESC );

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
	 *
	 * @param string $field
	 * @param string $search
	 * @return \Title[]
	 */
	protected function runFullSearch( $field, $search ) {
		if ( $search === '' ) {
			return [];
		}

		$lookup = new \BS\ExtendedSearch\Lookup();
		$lookup->setQueryString( [
			'query' => $search,
			'default_operator' => 'AND',
			'fields' => [ $field ]
		] );
		$lookup->addTermsFilter( 'namespace', $this->namespaces );
		$lookup->setSize( $this->limit );
		$lookup->setFrom( $this->offset );
		$lookup->addSort( '_score', Lookup::SORT_DESC );
		$lookup->addSort( 'mtime', Lookup::SORT_DESC );

		$resultSet = $this->backend->runLookup( $lookup );

		if ( property_exists( $resultSet, 'exception' ) ) {
			return [];
		}

		$titles = [];
		foreach ( $resultSet->results as $item ) {
			if ( !isset( $item['prefixed_title'] ) ) {
				continue;
			}

			$title = \Title::newFromText( $item['prefixed_title'] );
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
			$lb = Services::getInstance()->getDBLoadBalancer();
			$class = \BS\ExtendedSearch\Setup::getSearchEngineClass( $lb );
			$this->fallbackSearchEngine = new $class( $lb );
		}
		return $this->fallbackSearchEngine;
	}
}
