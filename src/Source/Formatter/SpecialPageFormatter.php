<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\SearchResult;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class SpecialPageFormatter extends Base {

	/**
	 *
	 * @param array $defaultResultStructure
	 * @return array
	 */
	public function getResultStructure( $defaultResultStructure = [] ): array {
		$defaultResultStructure['page_anchor'] = 'page_anchor';
		$defaultResultStructure['namespace_text'] = 'namespace_text';

		return $defaultResultStructure;
	}

	/**
	 *
	 * @param array &$resultData
	 * @param SearchResult $resultObject
	 */
	public function format( &$resultData, $resultObject ): void {
		if ( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}
		parent::format( $resultData, $resultObject );
		$page = MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage( $resultData['basename'] );
		if ( $page ) {
			$resultData['page_anchor'] = $this->getTraceablePageAnchor(
				$page->getPageTitle(), $page->getLocalName()
			);
		}
	}

	/**
	 *
	 * @param array $result
	 * @return bool
	 */
	protected function isFeatured( $result ) {
		$filters = $this->lookup->getFilters();
		if ( isset( $filters['terms']['namespace'] ) ) {
			foreach ( $filters['terms']['namespace'] as $nsIndex ) {
				if ( (int)$nsIndex == NS_SPECIAL ) {
					return parent::isFeatured( $result );
				}
			}
		}
		return false;
	}

	/**
	 *
	 * @param array &$results
	 * @param array $searchData
	 */
	public function formatAutocompleteResults( &$results, $searchData ): void {
		parent::formatAutocompleteResults( $results, $searchData );

		foreach ( $results as &$result ) {
			if ( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			$origBasename = $result['basename'];

			if ( -1 != $searchData['namespace'] ) {
				$result['basename'] = $result['prefixed_title'];
			}

			$title = Title::makeTitle( NS_SPECIAL, $origBasename );
			if ( $title instanceof Title ) {
				$result['page_anchor'] = $this->getTraceablePageAnchor( $title, $title->getText() );
			}
		}
	}
}
