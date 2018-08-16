<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\Source\Formatter\Base;
use BlueSpice\DynamicFileDispatcher\Params;
use BlueSpice\DynamicFileDispatcher\ArticlePreviewImage;

class WikiPageFormatter extends Base {
	public function getResultStructure ( $defaultResultStructure = [] ) {
		$resultStructure = $defaultResultStructure;
		$resultStructure['page_anchor'] = 'page_anchor';
		$resultStructure['highlight'] = 'highlight';
		$resultStructure['secondaryInfos']['top']['items'][] = [
			"name" => "sections"
		];
		$resultStructure['secondaryInfos']['bottom']['items'][] = [
			"name" => "categories"
		];

		//$resultStructure['imageUri'] = "image_uri";

		$resultStructure['featured']['highlight'] = "rendered_content_snippet";
		$resultStructure['featured']['imageUri'] = "image_uri";

		return $resultStructure;
	}

	public function format( &$result, $resultObject ) {
		if( $this->source->getTypeKey() != $resultObject->getType() ) {
			return;
		}

		parent::format( $result, $resultObject );

		$result['categories'] = $this->formatCategories( $result['categories'] );
		$result['sections'] = $this->formatSection( $result );
		$result['highlight'] = $this->getHighlight( $resultObject );
		$result['rendered_content_snippet'] = $this->getRenderedContentSnippet( $result['rendered_content'] );

		$result['display_text'] = $result['prefixed_title'];

		$this->addAnchorAndImageUri( $result );
	}

	protected function isFeatured( $result ) {
		if( $this->lookup == null ) {
			return false;
		}

		$queryString = $this->lookup->getQueryString();
		if( isset( $queryString['query'] ) == false ) {
			return false;
		}

		$term = $queryString['query'];

		$filters = $this->lookup->getFilters();
		$namespaceFilters = [];
		if( isset( $filters['terms']['namespace_text'] ) ) {
			$namespaceFilters = $filters['terms']['namespace_text'];
		}

		$pageTitle = $result['prefixed_title'];

		if( !empty( $namespaceFilters ) ) {
			$pageTitle = $this->removeNamespace( $pageTitle );
		}

		if( strtolower( $term ) == strtolower( $pageTitle ) ) {
			return true;
		}

		return false;
	}

	protected function addAnchorAndImageUri( &$result ) {
		$title = \Title::newFromText( $result['prefixed_title'] );
		if( $title instanceof \Title && $title->getNamespace() == $result['namespace'] ) {
			$result['page_anchor'] = $this->getPageAnchor( $title, $result['display_text'] );
			if( $title->exists() ) {
				$result['image_uri'] = $this->getImageUri( $result['prefixed_title'], 150 );
			}
		}
	}

	protected function formatCategories( $categories ) {
		if( empty( $categories ) ) {
			return;
		}

		$moreCategories = false;
		$formattedCategories = [];
		foreach( $categories as $idx => $category ) {
			if( $idx > 2 ) {
				$moreCategories = true;
				break;
			}
			$categoryTitle = \Title::makeTitle( NS_CATEGORY, $category );
			$formattedCategories[] = $this->linkRenderer->makeLink( $categoryTitle, $category );
		}
		return implode( Base::VALUE_SEPARATOR, $formattedCategories ) . ( $moreCategories ? Base::MORE_VALUES_TEXT : '' );
	}

	protected function formatSection( $result ) {
		$title = \Title::newFromText( $result['prefixed_title'] );
		$sections = [];
		$moreSections = false;
		foreach( $result['sections'] as $idx => $section ) {
			if( $idx > 2 ) {
				$moreSections = true;
				break;
			}
			$linkTarget = $title->createFragmentTarget( $section );
			$sections[] = $this->linkRenderer->makeLink( $linkTarget, $section );
		}
		return implode( Base::VALUE_SEPARATOR, $sections ) . ( $moreSections ? Base::MORE_VALUES_TEXT : '' );;
	}

	protected function getHighlight( $resultObject ) {
		$highlights = $resultObject->getHighlights();
		$highlightParts = [];
		if( isset( $highlights['rendered_content'] ) ) {
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
		if( !( $title instanceof \Title ) || $title->exists() == false ) {
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
		if( null == $dfdUrlBuilder ) {
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

	public function formatAutocompleteResults( &$results, $searchData ) {
		parent::formatAutocompleteResults( $results, $searchData );

		foreach( $results as &$result ) {
			if( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			$result['display_text'] = $result['prefixed_title'];

			$this->addAnchorAndImageUri( $result );
		}
	}

	public function rankAutocompleteResults( &$results, $searchData ) {
		foreach( $results as &$result ) {
			if( $result['type'] !== $this->source->getTypeKey() ) {
				continue;
			}

			$pageTitle = $result['prefixed_title'];
			// If there is a namespace filter set, all results coming here will
			// already be in desired namespace, so we should match only non-namespace
			// part of a title to determine match rank.
			if( $searchData['namespace'] !== NS_MAIN ) {
				$pageTitle = $this->removeNamespace( $pageTitle );
			}

			if( strtolower( $pageTitle ) == strtolower( $searchData['value'] ) ) {
				$result['rank'] = self::AC_RANK_TOP;
			} else if( strpos( strtolower( $pageTitle ), strtolower( $searchData['value'] ) ) !== false ) {
				$result['rank'] = self::AC_RANK_NORMAL;
			} else {
				$result['rank'] = self::AC_RANK_SECONDARY;
			}

			$result['is_ranked'] = true;
		}
	}

	protected function removeNamespace( $prefixedTitle ) {
		$bits = explode( ':',$prefixedTitle );
		if( count( $bits ) == 2 ) {
			return $bits[1];
		} else {
			return $prefixedTitle;
		}
	}

	/**
	 * Increase score of results that have search term in base text,
	 * as opposed to in subpage
	 * This should happen anyway, as if a doc contain search term in basename AND
	 * in prefixed_title it will get scored higher
	 *
	 * @param array $results
	 * @param array $searchData
	 */
	public function scoreAutocompleteResults( &$results, $searchData ) {
		parent::scoreAutocompleteResults( $results, $searchData );
		foreach( $results as &$result ) {
			if( $this->getHasMatchInBasetext( $result[ 'basename' ], $searchData[ 'value' ] ) ) {
				$result['score'] += 2;
			}
		}
	}

	public function getHasMatchInBasetext( $basename, $searchValue ) {
		if( strpos( strtolower( $basename ), strtolower( $searchValue ) ) !== false ) {
			return true;
		}
		return false;
	}

}

