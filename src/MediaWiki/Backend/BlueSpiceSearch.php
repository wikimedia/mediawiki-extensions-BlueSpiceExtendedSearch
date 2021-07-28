<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

use BlueSpice\Services;
use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use MediaWiki\MediaWikiServices;
use SearchResult;

class BlueSpiceSearch extends \SearchEngine {
	/** @var null  */
	protected $fallbackSearchEngine = null;
	/** @var Backend */
	protected $backend;
	/** @var string */
	protected $defaultOperator;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->backend = $services->getService( 'BSExtendedSearchBackend' );
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$this->defaultOperator = $config->get( 'ESDefaultSearchOperator' );
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
		$res = $this->runNGramSearch( $term );
		$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
		foreach ( $res as $title ) {
			$searchResultSet->add(
				SearchResult::newFromTitle( $title, $searchResultSet )
			);
		}

		return $searchResultSet;
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
			'default_operator' => $this->defaultOperator,
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
				!$this->containsSearchTerm( $search, $title )
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
	 * @param string $search
	 * @param \Title $title
	 * @return bool
	 */
	private function containsSearchTerm( $search, $title ) {
		return strpos(
			strtolower( $title->getDBkey() ),
			strtolower( str_replace( ' ', '_', $search ) )
		) !== false;
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
			$services = Services::getInstance();
			$lb = $services->getDBLoadBalancer();
			// in all unoffical branches tests will against master. This 'fixes' all tests
			// from every extension, that reqires BlueSpiceExtendedSearch
			$version = $services->getConfigFactory()->makeConfig( 'bsg' )->get( 'Version' );
			if ( version_compare( $version, '1.33', '<' ) ) {
				$lb = $lb->getConnection( DB_REPLICA );
			}
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
