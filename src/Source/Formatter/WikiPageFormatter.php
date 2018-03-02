<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\Source\Formatter\Base;
use BlueSpice\DynamicFileDispatcher\Params;
use BlueSpice\DynamicFileDispatcher\ArticlePreviewImage;
use MediaWiki\MediaWikiServices;

class WikiPageFormatter extends Base {
	public function modifyResultStructure ( &$resultStructure ) {
		$resultStructure['headerText'] = 'prefixed_title';
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
		$result['image_uri'] = $this->getImageUri( $result['prefixed_title'], $result['namespace'] );
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
			$formattedCategories[] = \Linker::link( $categoryTitle, $category );
		}
		return implode( Base::VALUE_SEPARATOR, $formattedCategories ) . ( $moreCategories ? Base::MORE_VALUES_TEXT : '' );
	}

	protected function formatSection( $result ) {
		$title = \Title::makeTitle( $result['namespace'], $result['prefixed_title'] );
		$sections = [];
		foreach( $result['sections'] as $section ) {
			$sections[] = \Linker::link( $title, $section ); //How to add fragment?
		}
		return implode( Base::VALUE_SEPARATOR, $sections );
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
	protected function getImageUri( $prefixedTitle, $ns ) {
		$title = \Title::makeTitle( $ns, $prefixedTitle );
		if( !( $title instanceof \Title ) || $title->exists() == false ) {
			return '';
		}

		$params = [
			Params::MODULE => 'articlepreviewimage',
			ArticlePreviewImage::WIDTH => 102,
			ArticlePreviewImage::TITLETEXT => $title->getFullText(),
		];
		$dfdUrlBuilder = MediaWikiServices::getInstance()->getService(
			'BSDynamicFileDispatcherUrlBuilder'
		);
		$url = $dfdUrlBuilder->build(
			new Params( $params )
		);

		return $url;
	}
}

