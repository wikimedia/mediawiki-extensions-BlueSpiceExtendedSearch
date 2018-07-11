<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

class BlueSpiceSearch extends \SearchEngine {

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
}