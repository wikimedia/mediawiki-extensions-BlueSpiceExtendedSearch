<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

use ISearchResultSet;
use MediaWiki\Title\Title;
use RevisionSearchResult;
use SearchResult;

class BlueSpiceSearchResult extends RevisionSearchResult {
	/** @var string */
	protected $textSnippet = '';

	/**
	 * Return a new SearchResult and initializes it with a title.
	 *
	 * @param Title $title
	 * @param ISearchResultSet|null $parentSet
	 * @return SearchResult
	 */
	public static function newFromTitle( $title, ?ISearchResultSet $parentSet = null ) {
		$result = new static( $title );
		if ( $parentSet ) {
			$parentSet->augmentResult( $result );
		}
		return $result;
	}

	/**
	 * @param string $snippet
	 */
	public function setTextSnippet( $snippet ) {
		$this->textSnippet = $snippet;
	}

	/**
	 * @param array $terms
	 * @return string
	 */
	public function getTextSnippet( $terms = [] ) {
		return $this->textSnippet;
	}
}
