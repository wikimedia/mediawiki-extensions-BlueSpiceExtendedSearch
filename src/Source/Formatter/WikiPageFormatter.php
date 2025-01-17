<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\SearchResult;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class WikiPageFormatter extends Base {

	/**
	 *
	 * @param array $defaultResultStructure
	 * @return array
	 */
	public function getResultStructure( $defaultResultStructure = [] ): array {
		$resultStructure = $defaultResultStructure;
		$resultStructure['page_anchor'] = 'page_anchor';
		$resultStructure['original_title'] = 'original_title';
		$resultStructure['highlight'] = 'highlight';
		$resultStructure['secondaryInfos']['top']['items'][] = [
			"name" => "sections",
			"showInRightLinks" => true
		];
		$resultStructure['secondaryInfos']['top']['items'][] = [
			"name" => "file-usage",
			"showInRightLinks" => true
		];
		$resultStructure['secondaryInfos']['top']['items'][] = [
			"name" => "redirects"
		];
		$resultStructure['secondaryInfos']['bottom']['items'][] = [
			"name" => "categories"
		];

		// $resultStructure['imageUri'] = "image_uri";

		$resultStructure['featured']['highlight'] = "rendered_content_snippet";
		$resultStructure['featured']['imageUri'] = "image_uri";

		return $resultStructure;
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

		if ( $resultData['is_redirect'] === true ) {
			$this->formatRedirect( $resultData );
			return;
		}
		$resultData['categories'] = $this->formatCategories( $resultData['categories'] );
		$resultData['highlight'] = $this->getHighlight( $resultObject );
		$resultData['sections'] = $this->getSections( $resultData );
		$resultData['redirects'] = $this->formatRedirectedFrom( $resultData );
		$resultData['rendered_content_snippet'] = $this->getRenderedContentSnippet( $resultData['rendered_content'] );

		if ( $resultData['display_title'] !== '' ) {
			$resultData['basename'] = $resultData['display_title'];
		}
		$resultData['original_title'] = $this->getOriginalTitleText( $resultData );

		$resultData['file-usage'] = '';
		if ( $resultData['namespace'] === NS_FILE ) {
			$resultData['file-usage'] = $this->getFileUsage( $resultData['prefixed_title'] );
		}

		$this->addAnchorAndImageUri( $resultData );
	}

	/**
	 *
	 * @param array $result
	 * @return bool
	 */
	protected function isFeatured( $result ) {
		if ( $this->lookup == null ) {
			return false;
		}

		$queryString = $this->lookup->getQueryString();
		if ( isset( $queryString['query'] ) == false ) {
			return false;
		}

		$term = $queryString['query'];

		$filters = $this->lookup->getFilters();
		$namespaceFilters = [];
		if ( isset( $filters['terms']['namespace_text'] ) ) {
			$namespaceFilters = $filters['terms']['namespace_text'];
		}

		$pageTitle = $result['prefixed_title'];

		if ( empty( $namespaceFilters ) ) {
			$pageTitle = $this->removeNamespace( $pageTitle );
		}

		if ( strtolower( $term ) == strtolower( $pageTitle ) ) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * @param array &$result
	 */
	protected function addAnchorAndImageUri( &$result ) {
		$title = Title::newFromText( $result['prefixed_title'] );
		if ( $title instanceof Title && $title->getNamespace() == $result['namespace'] ) {
			$result['page_anchor'] = $this->getTraceablePageAnchor( $title, $result['display_title'] );
			if ( $title->exists() ) {
				$result['image_uri'] = $this->getImageUri( $result['prefixed_title'], 150 );
			}
		}
	}

	/**
	 *
	 * @param array $categories
	 * @return string|null
	 */
	protected function formatCategories( $categories ) {
		if ( empty( $categories ) ) {
			return null;
		}

		$moreCategories = false;
		$formattedCategories = [];
		foreach ( $categories as $idx => $category ) {
			if ( $idx > 2 ) {
				$moreCategories = true;
				break;
			}
			$categoryTitle = Title::makeTitle( NS_CATEGORY, $category );
			$formattedCategories[] = $this->linkRenderer->makeLink( $categoryTitle, $categoryTitle->getText() );
		}
		return implode( Base::VALUE_SEPARATOR, $formattedCategories ) . ( $moreCategories ? Base::MORE_VALUES_TEXT : '' );
	}

	/**
	 * Get sections that match the search term
	 *
	 * @param array $result
	 * @return string Formatted sections
	 */
	protected function getSections( $result ) {
		$highlightedTerm = $this->getHighlightedTerm( $result );
		$sections = $result[ 'sections' ];

		if ( count( $sections ) === 0 || $highlightedTerm === '' ) {
			return '';
		}

		$matchedSections = [];
		foreach ( $sections as $section ) {
			$sectionText = urldecode( str_replace( '.', '%', $section ) );
			$sectionText = str_replace( '%', '.', $sectionText );
			$lcTerm = strtolower( $highlightedTerm );
			$lcSection = strtolower( $sectionText );
			if ( strpos( $lcSection, $lcTerm ) !== false ) {
				$matchedSections[] = $sectionText;
			}
		}

		return $this->formatSections( $result, $matchedSections );
	}

	/**
	 *
	 * @param array $result
	 * @return string
	 */
	protected function getHighlightedTerm( $result ) {
		if ( $result[ 'highlight' ] == '' ) {
			return '';
		}

		$hightlightedTerm = [];
		preg_match( '/<b>(.*?)<\/b>/', $result[ 'highlight' ], $hightlightedTerm );
		if ( !isset( $hightlightedTerm[ 1 ] ) ) {
			return '';
		}
		return strtolower( $hightlightedTerm[ 1 ] );
	}

	/**
	 *
	 * @param array $result
	 * @param array $sectionsToAdd
	 * @return string
	 */
	protected function formatSections( $result, $sectionsToAdd ) {
		$title = Title::newFromText( $result['prefixed_title'] );
		$sections = [];
		$moreSections = false;
		foreach ( $sectionsToAdd as $idx => $section ) {
			if ( $idx > 2 ) {
				$moreSections = true;
				break;
			}
			$linkTarget = $title->createFragmentTarget( $section );
			$displayText = str_replace( '_', ' ', $section );
			if ( strlen( $displayText ) > 25 ) {
				$displayText = substr( $displayText, 0, 25 ) . Base::MORE_VALUES_TEXT;
			}
			$sections[] = $this->linkRenderer->makeLink( $linkTarget, $displayText );
		}

		$sectionText = implode( Base::VALUE_SEPARATOR, $sections );
		if ( $moreSections ) {
			$sectionText .=
				wfMessage(
					'bs-extendedseach-wikipage-section-more-text',
					( count( $result['sections'] ) - 3 )
				)->plain();
		}
		return $sectionText;
	}

	/**
	 *
	 * @param array $result
	 * @return string
	 */
	protected function formatRedirectedFrom( $result ) {
		if ( empty( $result[ 'redirected_from' ] ) ) {
			return '';
		}

		$redirs = [];
		foreach ( $result[ 'redirected_from'] as $prefixedTitle ) {
			$redirTitle = Title::newFromText( $prefixedTitle );
			if ( $redirTitle instanceof Title === false ) {
				continue;
			}

			$displayText = str_replace( '_', ' ', $prefixedTitle );
			if ( strlen( $displayText ) > 25 ) {
				$displayText = substr( $displayText, 0, 25 ) . Base::MORE_VALUES_TEXT;
			}
			$redirs[] = $this->linkRenderer->makeLink( $redirTitle, $displayText );
		}

		return implode( Base::VALUE_SEPARATOR, $redirs );
	}

	/**
	 *
	 * @param SearchResult $resultObject
	 * @return string
	 */
	protected function getHighlight( $resultObject ) {
		$highlights = $resultObject->getParam( 'highlight' );
		if ( isset( $highlights['rendered_content'] ) ) {
			return implode( ' ', $highlights['rendered_content'] );
		}
		return '';
	}

	/**
	 * Returns only portion of rendered content
	 * that is displayed in featured results
	 *
	 * @param string $renderedContent
	 * @return string
	 */
	protected function getRenderedContentSnippet( $renderedContent ) {
		return substr( $renderedContent, 0, 500 ) . Base::MORE_VALUES_TEXT;
	}

	/**
	 * Gets the URL for the article preview image
	 *
	 * @param string $prefixedTitle
	 * @param int $width
	 * @return string
	 */
	protected function getImageUri( $prefixedTitle, $width = 102 ) {
		$title = Title::newFromText( $prefixedTitle );
		if ( !( $title instanceof Title ) || $title->exists() == false ) {
			return '';
		}

		$dfdUrlBuilder = $this->source->getBackend()->getService(
			'MWStake.DynamicFileDispatcher.Factory'
		);

		return $dfdUrlBuilder->getUrl(
			'articlepreviewimage',
			[
				'width' => $width,
				'titletext' => $title->getFullText(),
			]
		);
	}

	/**
	 *
	 * @param array &$result
	 */
	protected function formatRedirect( &$result ) {
		$title = Title::newFromText( $result['prefixed_title'] );
		$redirTarget = Title::newFromText( $result['redirects_to'] );
		if ( $redirTarget instanceof Title === false ) {
			return;
		}

		$result['page_anchor'] = $this->getTraceablePageAnchor( $title, $result['display_title'] );
		$this->addRedirectAttributes( $result );
	}

	/**
	 * @param array &$result
	 * @return void
	 */
	protected function addRedirectAttributes( array &$result ) {
		$redirTarget = Title::newFromText( $result['redirects_to'] );
		if ( $redirTarget instanceof Title === false ) {
			return;
		}
		$result['is_redirect'] = 1;
		$result['redirect_target_anchor'] = $this->getTraceablePageAnchor( $redirTarget, $result['redirects_to'] );

		$icons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchIcons' );

		$scriptPath = $this->getContext()->getConfig()->get( 'ScriptPath' );
		if ( isset( $icons['redirect'] ) ) {
			$result['image_uri'] = $scriptPath . $icons['redirect'];
		}
	}

	/**
	 *
	 * @param array $result
	 * @return string
	 */
	protected function getOriginalTitleText( $result ) {
		$displayTitle = $result['display_title'];
		$prefixedTitle = $result['prefixed_title'];
		if ( $displayTitle != $prefixedTitle ) {
			return $prefixedTitle;
		}
		return '';
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

			if ( $result['display_title'] !== '' ) {
				$result['display_text'] = $result['display_title'];
				$result['original_title'] = $this->getOriginalTitleText( $result );
			} else {
				$result['display_text'] = $result['prefixed_title'];
			}
			if ( $result['is_redirect'] === true ) {
				$this->addRedirectAttributes( $result );
			}

			$this->addAnchorAndImageUri( $result );
		}
	}

	/**
	 *
	 * @param array &$results
	 * @param array $searchData
	 */
	public function rankAutocompleteResults( &$results, $searchData ): void {
		$top = $this->getACHighestScored( $results );
		foreach ( $results as &$result ) {
			if ( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			$this->assignRank( $result, $result['display_title'], $searchData, $top['_id'] );
			if ( $this->getOriginalTitleText( $result ) ) {
				$this->assignRank( $result, $result['prefixed_title'], $searchData, $top['_id'] );
			}

			$result['is_ranked'] = true;
		}
	}

	/**
	 * @param array &$result
	 * @param string $pageTitle
	 * @param array $searchData
	 * @param string $topId
	 */
	protected function assignRank( &$result, $pageTitle, $searchData, $topId ) {
		// If there is a namespace filter set, all results coming here will
		// already be in desired namespace, so we should match only non-namespace
		// part of a title to determine match rank.
		if ( $searchData['namespace'] !== NS_MAIN ) {
			$pageTitle = $this->removeNamespace( $pageTitle );
		}

		if ( isset( $searchData['mainpage'] ) ) {
			// If we are querying subpages, we dont want base page
			// as a result - kick it to secondary
			if ( strtolower( $pageTitle ) == strtolower( $searchData['mainpage'] ) ) {
				if ( !isset( $result['rank'] ) || $result['rank'] !== 'primary' ) {
					$result['rank'] = self::AC_RANK_SECONDARY;
				}
				return;
			}
		}

		$lcTitle = mb_strtolower( $pageTitle );
		$lcSearchTerm = mb_strtolower( $searchData['value'] );
		if ( strpos( $lcTitle, $lcSearchTerm ) === 0 && $topId === $result['_id'] ) {
			$result['rank'] = self::AC_RANK_TOP;
		} elseif ( $this->matchTokenized( $lcTitle, $lcSearchTerm ) ) {
			$result['rank'] = self::AC_RANK_NORMAL;
		} elseif ( !isset( $result['rank'] ) || !$result['rank'] ) {
			$result['rank'] = self::AC_RANK_SECONDARY;
		}
	}

	/**
	 *
	 * @param string $prefixedTitle
	 * @return string
	 */
	protected function removeNamespace( $prefixedTitle ) {
		$bits = explode( ':', $prefixedTitle );
		if ( count( $bits ) == 2 ) {
			return $bits[1];
		} else {
			return $prefixedTitle;
		}
	}

	/**
	 *
	 * @param Title $title
	 * @return string
	 */
	protected function getFileUsage( $title ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()
			->getConnection( DB_REPLICA );

		// Would be nice to get this info from the index w/o running another query
		$target = Title::newFromText( $title );
		$res = $dbr->select(
			[ 'imagelinks', 'page' ],
			[ 'page_namespace', 'page_title', 'il_to' ],
			[ 'il_to' => $target->getDBkey(), 'il_from = page_id' ],
			__METHOD__,
			[ 'LIMIT' => 5, 'ORDER BY' => 'il_from', ]
		);
		if ( $res->numRows() === 0 ) {
			return '';
		}

		$usedInPages = [];
		foreach ( $res as $row ) {
			$usedInPages[] = Title::makeTitle(
				$row->page_namespace,
				$row->page_title
			);
		}

		$morePages = false;
		$formattedPages = [];
		foreach ( $usedInPages as $idx => $pageTitle ) {
			if ( $idx > 2 ) {
				$morePages = true;
				break;
			}
			$formattedPages[] = $this->linkRenderer->makeLink( $pageTitle );
		}

		return implode( Base::VALUE_SEPARATOR, $formattedPages ) . ( $morePages ? Base::MORE_VALUES_TEXT : '' );
	}

}
