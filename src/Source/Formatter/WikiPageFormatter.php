<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BlueSpice\DynamicFileDispatcher\Params;
use BlueSpice\DynamicFileDispatcher\ArticlePreviewImage;
use MediaWiki\MediaWikiServices;

class WikiPageFormatter extends Base {
	public function getResultStructure( $defaultResultStructure = [] ) {
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

	public function format( &$result, $resultObject ) {
		if ( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}

		parent::format( $result, $resultObject );

		if ( $result['is_redirect'] === true ) {
			return $this->formatRedirect( $result );
		}
		$result['categories'] = $this->formatCategories( $result['categories'] );
		$result['highlight'] = $this->getHighlight( $resultObject );
		$result['sections'] = $this->getSections( $result );
		$result['redirects'] = $this->formatRedirectedFrom( $result );
		$result['rendered_content_snippet'] = $this->getRenderedContentSnippet( $result['rendered_content'] );

		if ( $result['display_title'] !== '' ) {
			$result['basename'] = $result['display_title'];
		}
		$result['original_title'] = $this->getOriginalTitleText( $result );

		$result['file-usage'] = '';
		if ( $result['namespace'] === NS_FILE ) {
			$result['file-usage'] = $this->getFileUsage( $result['prefixed_title'] );
		}

		$this->addAnchorAndImageUri( $result );
	}

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

	protected function addAnchorAndImageUri( &$result ) {
		$title = \Title::newFromText( $result['prefixed_title'] );
		if ( $title instanceof \Title && $title->getNamespace() == $result['namespace'] ) {
			$result['page_anchor'] = $this->getPageAnchor( $title, $result['display_title'] );
			if ( $title->exists() ) {
				$result['image_uri'] = $this->getImageUri( $result['prefixed_title'], 150 );
			}
		}
	}

	protected function formatCategories( $categories ) {
		if ( empty( $categories ) ) {
			return;
		}

		$moreCategories = false;
		$formattedCategories = [];
		foreach ( $categories as $idx => $category ) {
			if ( $idx > 2 ) {
				$moreCategories = true;
				break;
			}
			$categoryTitle = \Title::makeTitle( NS_CATEGORY, $category );
			$formattedCategories[] = $this->linkRenderer->makeLink( $categoryTitle, $category );
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
			$lcTerm = strtolower( $highlightedTerm );
			$lcSection = strtolower( $section );
			if ( strpos( $lcSection, $lcTerm ) !== false ) {
				$matchedSections[] = $section;
			}
		}

		return $this->formatSections( $result, $matchedSections );
	}

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

	protected function formatSections( $result, $sectionsToAdd ) {
		$title = \Title::newFromText( $result['prefixed_title'] );
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

	protected function formatRedirectedFrom( $result ) {
		if ( empty( $result[ 'redirected_from' ] ) ) {
			return '';
		}

		$redirs = [];
		foreach ( $result[ 'redirected_from'] as $prefixedTitle ) {
			$redirTitle = \Title::newFromText( $prefixedTitle );
			if ( $redirTitle instanceof \Title === false ) {
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

	protected function getHighlight( $resultObject ) {
		$highlights = $resultObject->getHighlights();
		$highlightParts = [];
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
	 * @param string $ns
	 * @return string
	 */
	protected function getImageUri( $prefixedTitle, $width = 102 ) {
		$title = \Title::newFromText( $prefixedTitle );
		if ( !( $title instanceof \Title ) || $title->exists() == false ) {
			return '';
		}

		$params = [
			Params::MODULE => 'articlepreviewimage',
			ArticlePreviewImage::WIDTH => $width,
			ArticlePreviewImage::TITLETEXT => $title->getFullText(),
		];
		$dfdUrlBuilder = $this->source->getBackend()->getService(
			'BSDynamicFileDispatcherUrlBuilder'
		);
		if ( null == $dfdUrlBuilder ) {
			return '';
		}

		$url = $dfdUrlBuilder->build(
			new Params( $params )
		);

		return $url;
	}

	protected function getPageAnchor( $title, $text ) {
		return $this->linkRenderer->makeLink( $title, $text );
	}

	protected function formatRedirect( &$result ) {
		$title = \Title::newFromText( $result['prefixed_title'] );
		$redirTarget = \Title::newFromText( $result['redirects_to'] );
		if ( $redirTarget instanceof \Title === false ) {
			return;
		}
		$result['is_redirect'] = 1;
		$result['page_anchor'] = $this->getPageAnchor( $title, $result['display_title'] );
		$result['redirect_target_anchor'] = $this->getPageAnchor( $redirTarget, $result['redirects_to'] );

		$icons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchIcons' );

		$scriptPath = $this->getContext()->getConfig()->get( 'ScriptPath' );
		if ( isset( $icons['redirect'] ) ) {
			$result['image_uri'] = $scriptPath . $icons['redirect'];
		}
	}

	protected function getOriginalTitleText( $result ) {
		$displayTitle = $result['display_title'];
		$prefixedTitle = $result['prefixed_title'];
		if ( $displayTitle != $prefixedTitle ) {
			return $prefixedTitle;
		}
		return '';
	}

	public function formatAutocompleteResults( &$results, $searchData ) {
		parent::formatAutocompleteResults( $results, $searchData );

		foreach ( $results as &$result ) {
			if ( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			if ( $result['display_title'] !== '' ) {
				$result['display_text'] = $result['display_title'];
			} else {
				$result['display_text'] = $result['prefixed_title'];
			}

			$this->addAnchorAndImageUri( $result );
		}
	}

	public function rankAutocompleteResults( &$results, $searchData ) {
		$top = $this->getACHighestScored( $results );
		foreach ( $results as &$result ) {
			if ( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			$pageTitle = $result['display_title'];
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
					$result['rank'] = self::AC_RANK_SECONDARY;
					$result['is_ranked'] = true;
					continue;
				}
			}

			$lcTitle = strtolower( $pageTitle );
			$lcSearchTerm = strtolower( $searchData['value'] );
			if ( strpos( $lcTitle, $lcSearchTerm ) === 0 && $top['_id'] === $result['_id'] ) {
				$result['rank'] = self::AC_RANK_TOP;
			} elseif ( $this->matchTokenized( $lcTitle, $searchData['value'] ) ) {
				$result['rank'] = self::AC_RANK_NORMAL;
			} else {
				$result['rank'] = self::AC_RANK_SECONDARY;
			}

			$result['is_ranked'] = true;
		}
	}

	protected function removeNamespace( $prefixedTitle ) {
		$bits = explode( ':', $prefixedTitle );
		if ( count( $bits ) == 2 ) {
			return $bits[1];
		} else {
			return $prefixedTitle;
		}
	}

	protected function getFileUsage( $title ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()
			->getConnection( DB_REPLICA );

		// Would be nice to get this info from the index w/o running another query
		$target = \Title::newFromText( $title );
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
			$usedInPages[] = \Title::makeTitle(
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
