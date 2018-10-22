<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

class BlueSpiceSearch extends \SearchEngine {
	protected $fallbackSearchEngine = null;

	public function searchText( $term ) {
		$oBackend = \BS\ExtendedSearch\Backend::instance();
		$lookup = new \BS\ExtendedSearch\Lookup();
		$lookup->setQueryString([
			'query' => $term,
			'default_operator' => 'AND',
			'fields' => ['source_content']
		]);
		$lookup->addTermsFilter( 'namespace', $this->namespaces );
		$this->resultSet = $oBackend->runLookup( $lookup );

		$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
		foreach ( $this->resultSet->results as $item ) {

			if ( isset( $item['prefixed_title'] ) ) {
				continue;
			}
			$searchResultSet->add(
				SearchResult::newFromTitle(
					\Title::newFromText( $item['prefixed_title'] ),
					$searchResultSet
				)
			);
		}
		return $searchResultSet;
	}

	public function searchTitle( $term ) {
		$oBackend = \BS\ExtendedSearch\Backend::instance();
		$lookup = new \BS\ExtendedSearch\Lookup();
		$lookup->setQueryString([
			'query' => $term,
			'default_operator' => 'AND',
			'fields' => ['basename']
		]);
		$lookup->addTermsFilter( 'namespace', $this->namespaces );
		$this->resultSet = $oBackend->runLookup( $lookup );

		$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
		foreach ( $this->resultSet->results as $item ) {

			if ( empty( $item['prefixed_title'] ) ) {
				continue;
			}
			$searchResultSet->add(
				SearchResult::newFromTitle(
					\Title::newFromText( $item['prefixed_title'] ),
					$searchResultSet
				)
			);
		}
		return $searchResultSet;
	}

	protected function searchContainedSyntax( $term ) {
		return false;
	}

	public function update( $id, $title, $text ) {
		$this->getFallbackSearchEngine()->update( $id, $title, $text );
	}

	public function updateTitle( $id, $title ) {
		$this->getFallbackSearchEngine()->updateTitle( $id, $title );
	}

	public function delete( $id, $title ) {
		$this->getFallbackSearchEngine()->delete( $id, $title );
	}

	/**
	 * Gets default search engine based on DB type
	 *
	 * @return \SearchEngine
	 */
	protected function getFallbackSearchEngine() {
		if( $this->fallbackSearchEngine === null ) {
			$db = wfGetDB( DB_REPLICA );
			$class = \BS\ExtendedSearch\Setup::getSearchEngineClass( $db );
			$this->fallbackSearchEngine = new $class( $db );
		}
		return $this->fallbackSearchEngine;
	}
}