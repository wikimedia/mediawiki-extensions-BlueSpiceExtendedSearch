<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Setup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SearchResult;
use SearchSuggestionSet;

class BlueSpiceSearch extends \SearchEngine {
	/** @var \SearchEngine|null */
	protected $fallbackSearchEngine = null;
	/** @var \BS\ExtendedSearch\Backend */
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
		return $this->fullSearchWrapper( $term, [], false );
	}

	/**
	 *
	 * @param string $term
	 * @return SearchResultSet
	 */
	public function searchTitle( $term ) {
		if ( $term === '*' ) {
			return $this->fullSearchWrapper( $term );
		}

		$res = $this->runAutocompleteSearch( $term );
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
	 * @param array $sourceFields
	 * @param bool $titleMustContainTerm
	 * @return SearchResultSet
	 */
	protected function fullSearchWrapper( $term, $sourceFields = [], $titleMustContainTerm = true ) {
		$term = trim( $term );
		$results = $this->runFullSearch( $term, $sourceFields, $titleMustContainTerm );

		$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
		foreach ( $results as $resultData ) {
			$result = BlueSpiceSearchResult::newFromTitle( $resultData['title'], $searchResultSet );
			$result->setTextSnippet( $resultData['snippet'] );
			$searchResultSet->add( $result );
		}

		return $searchResultSet;
	}

	/**
	 * Used in opensearchApi
	 * @param string $search
	 * @return SearchSuggestionSet
	 */
	public function completionSearchWithVariants( $search ) {
		if ( trim( $search ) === '' ) {
			return SearchSuggestionSet::emptySuggestionSet();
		}
		$search = $this->normalizeNamespaces( $search );
		$results = $this->runFullSearch( $search, [ 'prefixed_title', 'rendered_content' ] );

		return SearchSuggestionSet::fromTitles( array_map( static function ( $results ) {
			return $results['title'];
		}, $results ) );
	}

	/**
	 *
	 * @param string $search
	 * @return \SearchSuggestionSet
	 */
	protected function completionSearchBackend( $search ) {
		$results = $this->runAutocompleteSearch( trim( $search ) );
		return \SearchSuggestionSet::fromTitles( $results );
	}

	/**
	 *
	 * @param string $search
	 * @return Title[]
	 */
	protected function runAutocompleteSearch( $search ) {
		if ( $search === '' ) {
			return [];
		}

		$acConfig = $this->backend->getAutocompleteConfig();

		$lookup = $this->getLookup();
		$lookup->setMatchQueryString( 'suggestions', $search );

		$results = $this->backend->runRawQuery( $lookup, [ 'wikipage' ] );
		$titles = [];
		foreach ( $results->getResults() as $item ) {
			$data = $item->getData();
			$title = Title::newFromText( $data['prefixed_title'] );
			if ( $title instanceof Title ) {
				$titles[] = $title;
			}
		}
		return $titles;
	}

	/**
	 * @param string $search
	 * @param array $sourceFields
	 * @param bool $titleMustContainTerm
	 * @return array
	 */
	protected function runFullSearch( $search, $sourceFields = [], $titleMustContainTerm = true ) {
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
			$title = Title::newFromText( $item['prefixed_title'] );
			if ( !( $title instanceof Title ) ) {
				continue;
			}
			if (
				$titleMustContainTerm &&
				$search !== '*' &&
				!$this->containsSearchTerm( $search, $title )
			) {
				continue;
			}

			$titles[] = [
				'title' => $title,
				'snippet' => $item['highlight'] ?? $item['rendered_content_snippet'] ?? '',
			];
		}

		return $titles;
	}

	/**
	 * @param string $search
	 * @param Title $title
	 * @return bool
	 */
	private function containsSearchTerm( $search, $title ) {
		return strpos(
			mb_strtolower( $title->getDBkey() ),
			mb_strtolower( str_replace( ' ', '_', $search ) )
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
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
			$class = Setup::getSearchEngineClass( $lb );
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
		$lookup->addSearchInTypes( [ 'wikipage' ] );
		$lookup->addSort( '_score', Lookup::SORT_DESC );

		return $lookup;
	}
}
