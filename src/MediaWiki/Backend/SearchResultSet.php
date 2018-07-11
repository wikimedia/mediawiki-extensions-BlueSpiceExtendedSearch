<?php
namespace BS\ExtendedSearch\MediaWiki\Backend;

class SearchResultSet extends \SearchResultSet {
	public $index = -1;
	private $results = [];

	public function __construct( $searchContainedSyntax ) {
		parent::__construct( $searchContainedSyntax );
	}

	public function numRows() {
		return count( $this->results );
	}

	public function getTotalHits() {
		return count( $this->results );
	}

	public function next() {
		$this->index++;
		if ( $this->index < count( $this->results ) ) {
			$nextResult = $this->results[$this->index];
		} else {
			return false;
		}
		return $nextResult;
	}

	public function rewind() {
		$this->index = -1;
	}

	public function add( $searchResult ) {
		$this->results[] = $searchResult;
	}
}