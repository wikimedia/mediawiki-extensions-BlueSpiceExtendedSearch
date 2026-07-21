<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

/**
 * Replaces the default query_string query with a bool/should structure
 * for improved title relevance ranking.
 *
 * Uses three additive should clauses:
 * 1. simple_query_string with OR (recall)
 * 2. simple_query_string with AND (precision boost)
 * 3. match_phrase on display_title (title boost)
 */
class BoolShouldQuery extends LookupModifier {

	/**
	 * @inheritDoc
	 */
	public function getPriority() {
		// Run after other QS modifiers so we can replace their work
		return 90;
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_SEARCH ];
	}

	public function apply() {
		if ( !$this->isEnabled() ) {
			return;
		}

		$queryString = $this->lookup->getQueryString();
		if ( empty( $queryString ) || !isset( $queryString['query'] ) ) {
			return;
		}

		$searchTerm = $queryString['query'];
		$titleBoost = $this->getTitleBoost();

		// Remove the existing query_string from must
		$this->lookup->clearQueryString();

		// Build the bool/should structure
		$shouldClauses = [
			[
				'simple_query_string' => [
					'query' => $searchTerm,
					'fields' => [ 'congregated' ],
					'default_operator' => 'or'
				]
			],
			[
				'simple_query_string' => [
					'query' => $searchTerm,
					'fields' => [ 'congregated' ],
					'default_operator' => 'and'
				]
			],
			[
				'match_phrase' => [
					'display_title' => [
						'query' => $searchTerm,
						'boost' => $titleBoost
					]
				]
			]
		];

		// Ensure the bool structure exists and set should clauses
		if ( !isset( $this->lookup['query'] ) ) {
			$this->lookup['query'] = [];
		}
		if ( !isset( $this->lookup['query']['bool'] ) ) {
			$this->lookup['query']['bool'] = [];
		}

		// Replace any existing should clauses — this modifier owns them entirely
		$this->lookup['query']['bool']['should'] = $shouldClauses;

		// Ensure at least one should clause must match
		$this->lookup['query']['bool']['minimum_should_match'] = 1;
	}

	public function undo() {
		if ( !$this->isEnabled() ) {
			return;
		}

		if ( isset( $this->lookup['query']['bool']['should'] ) ) {
			unset( $this->lookup['query']['bool']['should'] );
		}
		if ( isset( $this->lookup['query']['bool']['minimum_should_match'] ) ) {
			unset( $this->lookup['query']['bool']['minimum_should_match'] );
		}
	}

	/**
	 * @return bool
	 */
	private function isEnabled(): bool {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return (bool)$config->get( 'ESUseBoolShouldQuery' );
	}

	/**
	 * @return int
	 */
	private function getTitleBoost(): int {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return (int)$config->get( 'ESBoolShouldTitleBoost' );
	}
}
