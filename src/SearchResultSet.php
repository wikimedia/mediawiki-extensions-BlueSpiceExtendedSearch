<?php

namespace BS\ExtendedSearch;

class SearchResultSet {
	/** @var int */
	private $took;
	/** @var int */
	private $totalHits;
	/**	@var SearchResult[] */
	private $results = [];
	/** @var bool */
	private $isTimedOut;
	/** @var array */
	private $aggregations;
	/** @var array */
	private $suggest;

	/**
	 * @param array $raw
	 * @param Backend $backend
	 */
	public function __construct( array $raw, Backend $backend ) {
		$this->took = $raw['took'] ?? 0;
		$this->totalHits = $raw['hits']['total']['value'] ?? 0;
		$this->isTimedOut = $raw['timed_out'] ?? true;
		$this->aggregations = $raw['aggregations'] ?? [];
		$this->createResults( $raw['hits']['hits'] ?? $raw['hits'] ?? [], $backend );
		$this->suggest = $raw['suggest'] ?? [];
	}

	/**
	 * @param array $rawResults
	 * @param Backend $backend
	 *
	 * @return void
	 */
	protected function createResults( array $rawResults, Backend $backend ) {
		foreach ( $rawResults as $rawResult ) {
			$this->results[] = new SearchResult( $rawResult, $backend->typeFromIndexName( $rawResult['_index'] ) );
		}
	}

	/**
	 * @return int
	 */
	public function getTook(): int {
		return $this->took;
	}

	/**
	 * @return int
	 */
	public function getTotalHits(): int {
		return $this->totalHits;
	}

	/**
	 * @return SearchResult[]
	 */
	public function getResults(): array {
		// Filter out nulls - even though it cannot happen
		return array_filter( $this->results );
	}

	/**
	 * @return bool
	 */
	public function isTimedOut(): bool {
		return $this->isTimedOut;
	}

	/**
	 * @return array
	 */
	public function getAggregations(): array {
		return $this->aggregations;
	}

	/**
	 * @return array
	 */
	public function getSuggest(): array {
		return $this->suggest;
	}
}
