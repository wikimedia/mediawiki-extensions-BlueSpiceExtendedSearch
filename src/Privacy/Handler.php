<?php

namespace BS\ExtendedSearch\Privacy;

use BlueSpice\Privacy\IPrivacyHandler;
use BlueSpice\Privacy\Module\Transparency;
use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;

class Handler implements IPrivacyHandler {
	protected $db;

	public function __construct( \Database $db ) {
		$this->db = $db;
	}

	public function anonymize( $oldUsername, $newUsername ) {
		// Nothing to handle
		return \Status::newGood();
	}

	public function delete( \User $userToDelete, \User $deletedUser ) {
		$this->db->update(
			'bs_extendedsearch_history',
			[ 'esh_user' => $deletedUser->getId() ],
			[ 'esh_user' => $userToDelete->getId() ]
		);

		$this->db->delete(
			'bs_extendedsearch_relevance',
			[ 'esr_user' => $userToDelete->getId() ]
		);

		return \Status::newGood();
	}

	public function exportData( array $types, $format, \User $user ) {
		$data = [];
		if ( in_array( Transparency::DATA_TYPE_CONTENT, $types ) ) {
			$data[Transparency::DATA_TYPE_CONTENT] = $this->getContentData( $user );
		}
		if ( in_array( Transparency::DATA_TYPE_WORKING, $types ) ) {
			$data[Transparency::DATA_TYPE_WORKING] = $this->getWorkingData( $user );
		}

		return \Status::newGood( $data );
	}

	protected function getContentData( $user ) {
		$data = [];

		$lookup = new Lookup();
		$lookup->addHighlighter( 'rendered_content' );
		$lookup->setQueryString( [
			'query' => $user->getName(),
			'fields' => [
				'rendered_content'
			]
		] );
		$lookup->addSourceField( 'prefixed_title' );

		$searchBackend = Backend::instance();

		$client = $searchBackend->getClient();
		$search = new \Elastica\Search( $client );
		$index = $searchBackend->getIndexByType( 'wikipage' );
		$search->addIndex( $index );
		$results = $search->search( $lookup->getQueryDSL() );
		foreach( $results->getResults() as $resultObject ) {
			$prefixedTitle = $resultObject->getData()['prefixed_title'];
			$title = \Title::newFromText( $prefixedTitle );
			if ( $title instanceof \Title === false ) {
				continue;
			}

			$data[] = wfMessage(
				'bs-extendedsearch-privacy-transparency-content-highlight',
				$title->getPrefixedText(),
				$this->getFormattedHighlights( $resultObject->getHighlights() )
			)->plain();
		}

		return $data;
	}

	protected function getFormattedHighlights( $highlights ) {
		if ( !isset( $highlights['rendered_content' ] ) ) {
			return '';
		}

		$formatted = '';
		foreach( $highlights['rendered_content'] as $highlight ) {
			$highlight = preg_replace( "/<b>|<\/b>/", '', $highlight );
			$formatted .= $highlight . "\n";
		}

		return $formatted;
	}

	protected function getWorkingData( $user ) {
		$searchHistory = $this->getSearchHistory( $user );
		$searchRelevance = $this->getSearchRelevance( $user );

		$data = [];
		if ( !empty( $searchHistory ) ) {
			$data = $searchHistory;
		}
		if ( !empty( $searchRelevance ) ) {
			$data = array_merge( $data, $searchRelevance );
		}

		return $data;
	}

	protected function getSearchHistory( $user ) {
		$res = $this->db->select(
			'bs_extendedsearch_history',
			['esh_term', 'COUNT( esh_term ) as freq'],
			['esh_user' => $user->getId()],
			__METHOD__,
			['GROUP BY' => 'esh_term' ]
		);

		$terms = [];
		foreach( $res as $row ) {
			if ( empty( $row->esh_term ) ) {
				continue;
			}
			$terms[] = wfMessage(
				'bs-extendedsearch-privacy-transparency-history-item',
				$row->esh_term,
				$row->freq
			)->plain();
		}

		if ( empty( $terms  ) ) {
			return [];
		}

		return [
			wfMessage(
				'bs-extendedsearch-privacy-transparency-history-summary',
				implode( ',', $terms )
			)->plain()
		];
	}

	protected function getSearchRelevance( $user ) {
		// We can only show the number of relevant pages user has,
		// because only hashed doc IDs are stored
		$row = $this->db->selectRow(
			'bs_extendedsearch_relevance',
			[ 'COUNT( esr_user ) as relevant_pages'],
			[
				'esr_user' => $user->getId(),
				'esr_value' => 1
			],
			__METHOD__,
			['GROUP BY' => 'esr_user' ]
		);

		if ( !$row ) {
			return [];
		}

		return [
			wfMessage(
				'bs-extendedsearch-privacy-transparency-relevance',
				$row->relevant_pages,
				$user->getName()
			)->parse()
		];
	}
}
