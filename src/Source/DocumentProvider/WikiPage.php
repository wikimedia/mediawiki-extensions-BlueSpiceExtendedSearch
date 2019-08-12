<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BlueSpice\Services;

class WikiPage extends DecoratorBase {
	/**
	 * @var \Content
	 */
	protected $content;

	/**
	 * @var \ParserOutput
	 */
	protected $parserOutput;

	/**
	 *
	 * @param string $sUri
	 * @param \WikiPage $oWikiPage
	 * @return array
	 */
	public function getDataConfig( $sUri, $oWikiPage ) {
		$aDC = $this->oDecoratedDP->getDataConfig( $sUri, $oWikiPage );

		$this->content = $oWikiPage->getContent();
		$this->parserOutput = $this->content->getParserOutput( $oWikiPage->getTitle() );

		$aDC = array_merge( $aDC, [
			'basename' => $oWikiPage->getTitle()->getBaseText(),
			'basename_exact' => $oWikiPage->getTitle()->getBaseText(),
			'extension' => 'wiki',
			'mime_type' => 'text/x-wiki',
			'mtime' => wfTimestamp(
				TS_ISO_8601,
				$oWikiPage->getRevision()->getTimestamp()
			),
			'ctime' => wfTimestamp(
				TS_ISO_8601,
				$oWikiPage->getOldestRevision()->getTimestamp()
			),
			'size' => $oWikiPage->getTitle()->getLength(),
			'categories' => $this->getCategories( $oWikiPage ),
			'prefixed_title' => $oWikiPage->getTitle()->getPrefixedText(),
			'sections' => $this->getSections(),
			'source_content' => $this->getTextContent(),
			'rendered_content' => $this->getHTMLContent(),
			'namespace' => $oWikiPage->getTitle()->getNamespace(),
			'namespace_text' => $this->getNamespaceText( $oWikiPage ),
			'tags' => $this->getTags(),
			'is_redirect' => $oWikiPage->getTitle()->isRedirect(),
			'redirects_to' => $this->getRedirectsTo( $oWikiPage ),
			'redirected_from' => $this->getRedirects( $oWikiPage ),
			'page_language' => $oWikiPage->getTitle()->getPageLanguage()->getCode(),
			'display_title' => $this->getDisplayTitle( $oWikiPage->getTitle() ),
			'used_files' => $this->getUserFiles( $oWikiPage )
		] );

		return $aDC;
	}

	public function __destruct() {
		parent::__destruct();
		$this->parserOutput = null;
		$this->content = null;
		if ( \MediaWiki\MediaWikiServices::getInstance()->getParser()->getOptions() instanceof \ParserOptions ) {
			\MediaWiki\MediaWikiServices::getInstance()->getParser()->clearState();
		}
	}

	/**
	 *
	 * @param \WikiPage $oWikiPage
	 */
	protected function getNamespaceText( $oWikiPage ) {
		if ( $oWikiPage->getTitle()->getNamespace() === NS_MAIN ) {
			return wfMessage( 'bs-ns_main' )->plain();
		}
		return $oWikiPage->getTitle()->getNsText();
	}

	/**
	 *
	 * @param \WikiPage $oWikiPage
	 */
	protected function getCategories( $oWikiPage ) {
		$oCatTitles = $oWikiPage->getCategories();

		$aCategories = [];
		foreach ( $oCatTitles as $oCatTitle ) {
			if ( $oCatTitle instanceof \Title ) {
				$aCategories[] = $oCatTitle->getText();
			}
		}

		return $aCategories;
	}

	/**

	 * @return string
	 */
	protected function getTextContent() {
		$sText = '';
		if ( $this->content instanceof \Content ) {
			// maybe ContentHandler::getContentText is better?
			$sText = $this->content->getTextForSearchIndex();
		}
		return $this->stripTags( $sText );
	}

	/**
	 *
	 * @return string
	 */
	protected function getHTMLContent() {
		$sHtml = $this->parserOutput->getText( [
			'allowTOC' => false,
			'enableSectionEditLinks' => false
		] );
		return $this->stripTags( $sHtml );
	}

	/**
	 *
	 * @return array
	 */
	protected function getSections() {
		$aSections = [];
		$aRawSections = $this->parserOutput->getSections();
		foreach ( $aRawSections as $aRawSection ) {
			$aSections[] = $aRawSection['anchor'];
		}
		return $aSections;
	}

	protected function stripTags( $sText ) {
		$sText = strip_tags( $sText );
		$sText = preg_replace( '/<!--(.|\s)*?-->/', '', $sText );
		return trim( $sText );
	}

	/**
	 * Collects all tags that are present on page,
	 * and are also registered with Parser
	 *
	 * @param type $oWikiPage
	 * @return array
	 */
	protected function getTags() {
		$res = [];

		$registeredTags = \MediaWiki\MediaWikiServices::getInstance()->getParser()->getTags();
		$pageTags = $this->parseWikipageForTags();
		foreach ( $pageTags as $pageTag ) {
			if ( in_array( $pageTag, $registeredTags ) ) {
				$res[] = $pageTag;
			}
		}
		return $res;
	}

	/**
	 *
	 * @param type $oWikiPage
	 * @return array
	 */
	protected function parseWikipageForTags() {
		if ( $this->content instanceof \Content == false ) {
			return [];
		}
		$text = $this->content->getNativeData();
		$rawTags = [];
		preg_match_all( '/<([^\/\s>]+)(\s|>|\/>)/', $text, $rawTags );
		if ( isset( $rawTags[1] ) ) {
			if ( is_array( $rawTags[1] ) == false ) {
				return [ $rawTags[1] ];
			}

			return array_unique( $rawTags[1] );
		}
		return [];
	}

	protected function getRedirectsTo( \WikiPage $oWikiPage ) {
		if ( $oWikiPage->getTitle()->isRedirect() === false ) {
			return '';
		}

		$redirTitle = $oWikiPage->getRedirectTarget();
		if ( $redirTitle instanceof \Title ) {
			return $this->getDisplayTitle( $redirTitle );
		}
		return '';
	}

	protected function getRedirects( \WikiPage $oWikiPage ) {
		$redirs = $oWikiPage->getTitle()->getRedirectsHere();
		$indexable = [];
		foreach ( $redirs as $redirect ) {
			$indexable[] = $redirect->getPrefixedText();
		}

		return $indexable;
	}

	protected function getDisplayTitle( \Title $title ) {
		$pageProps = $this->getPageProps( $title );
		if ( isset( $pageProps['displaytitle'] ) && $pageProps['displaytitle'] !== '' ) {
			return $pageProps['displaytitle'];
		}
		return $title->getPrefixedText();
	}

	protected function getPageProps( \Title $title ) {
		return Services::getInstance()->getBSUtilityFactory()
			->getPagePropHelper( $title )->getPageProps();
	}

	protected function getUserFiles( \WikiPage $wikiPage ) {
		$parserOutput = $wikiPage->getContent()->getParserOutput( $wikiPage->getTitle() );
		$files = $parserOutput->getImages();

		return array_keys( $files );
	}
}
