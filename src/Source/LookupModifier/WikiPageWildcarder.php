<?php

namespace BS\ExtendedSearch\Source\LookupModifier;


class WikiPageWildcarder extends Base {
	protected $queryString;
	protected $originalQuery;
	protected $basePage;

	public function apply() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->originalQuery = $this->queryString['query'];

		if( $this->containsSubpages() ) {
			return $this->setSubpageSearch();
		}
	}

	protected function containsSubpages() {
		$parts = explode( '/', $this->originalQuery );
		if( count( $parts ) === 1 ) {
			return false;
		}
		return true;
	}

	protected function setSubpageSearch() {
		$parts = explode( '/', $this->originalQuery );
		$pageQuery = array_pop( $parts );
		$this->basePage = implode( '/', $parts );
		$this->queryString['query'] = $pageQuery;
		$this->oLookup->setQueryString( $this->queryString );
		$this->oLookup->addTermFilter( 'basename_exact', $this->basePage );
	}

	public function undo() {
		$this->queryString = $this->oLookup->getQueryString();
		$this->queryString['query'] = $this->originalQuery;
		$this->oLookup->setQueryString( $this->queryString );
		$this->oLookup->removeTermFilter( 'basename_exact', $this->basePage );
	}
}