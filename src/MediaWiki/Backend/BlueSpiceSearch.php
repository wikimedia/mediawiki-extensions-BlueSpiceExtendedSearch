<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

class BlueSpiceSearch extends \SearchEngine {

	public function searchText( $term ) {
		$oBackend = \BS\ExtendedSearch\Backend::instance();
		$lookup = new \BS\ExtendedSearch\Lookup();
		$lookup->setQueryString( $term );
		$this->resultSet = $oBackend->runLookup( $lookup );
		foreach ( $this->resultSet->results as $item ) {
			$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
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
		$lookup->setQueryString( $term );
		$this->resultSet = $oBackend->runLookup( $lookup );
		foreach ( $this->resultSet->results as $item ) {
			$searchResultSet = new SearchResultSet( $this->searchContainedSyntax( $term ) );
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
}