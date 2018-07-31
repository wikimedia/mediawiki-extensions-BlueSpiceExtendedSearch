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
			"name" => "namespace_text"
		];
		$resultStructure['secondaryInfos']['top']['items'][] = [
			"name" => "sections"
		];
		$resultStructure['secondaryInfos']['bottom']['items'][] = [
			"name" => "categories"
		];

		//All fields under "featured" key will only appear is result is featured
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

			//Do not show namespace part if user is already searching in particular NS
			if( $result['namespace'] != $searchData['namespace'] || $searchData['namespace'] === 0 ) {
				$result['display_text'] = $result['prefixed_title'];
			} else {
				$result['display_text'] = $result['basename'];
			}

			$this->addAnchorAndImageUri( $result );
		}
	}
}

