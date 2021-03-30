<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use Content;
use FatalError;
use Hooks;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\RevisionRecord;
use MWException;
use ParserOptions;
use ParserOutput;
use Title;
use WikiPage as WikiPageObject;

class WikiPage extends DecoratorBase {
	/** @var WikiPageObject */
	protected $wikipage;
	/** @var Content */
	protected $content;
	/** @var ParserOutput */
	protected $parserOutput;
	/** @var Title */
	protected $title;
	/** @var RevisionRecord */
	protected $revision;
	/** @var array */
	protected $pageProps = null;

	/**
	 *
	 * @param string $sUri
	 * @param WikiPageObject|null $wikiPage
	 * @return array
	 * @throws MWException
	 */
	public function getDataConfig( $sUri, $wikiPage ) {
		$aDC = $this->oDecoratedDP->getDataConfig( $sUri, null );
		$this->wikipage = $wikiPage;
		$this->assertWikiPage();

		$this->title = $this->wikipage->getTitle();
		$this->revision = $this->getRevision();
		$this->assertRevision();

		$this->content = $this->revision->getContent( 'main' );
		$this->parserOutput = $this->content->getParserOutput( $this->title );

		$aDC = array_merge( $aDC, [
			'basename' => $this->title->getBaseText(),
			'basename_exact' => $this->title->getBaseText(),
			'extension' => 'wiki',
			'mime_type' => 'text/x-wiki',
			'mtime' => wfTimestamp(
				TS_ISO_8601,
				$this->revision->getTimestamp()
			),
			'ctime' => wfTimestamp(
				TS_ISO_8601,
				$this->title->getFirstRevision()->getTimestamp()
			),
			'size' => $this->title->getLength(),
			'categories' => $this->getCategories(),
			'prefixed_title' => $this->title->getPrefixedText(),
			'sections' => $this->getSections(),
			'source_content' => $this->getTextContent(),
			'rendered_content' => $this->getHTMLContent(),
			'namespace' => $this->title->getNamespace(),
			'namespace_text' => $this->getNamespaceText( $this->title ),
			'tags' => $this->getTags(),
			'is_redirect' => $this->title->isRedirect(),
			'redirects_to' => $this->getRedirectsTo(),
			'redirected_from' => $this->getRedirects(),
			'page_language' => $this->title->getPageLanguage()->getCode(),
			'display_title' => $this->getDisplayTitle(),
			'used_files' => $this->getUsedFiles()
		] );

		return $aDC;
	}

	/**
	 * Destroy variables used while getting the document values
	 */
	public function __destruct() {
		parent::__destruct();
		$this->parserOutput = null;
		$this->content = null;
		if ( MediaWikiServices::getInstance()->getParser()->getOptions() instanceof ParserOptions ) {
			MediaWikiServices::getInstance()->getParser()->clearState();
		}
	}

	/**
	 *
	 * @param Title $title
	 * @param string|null $prop
	 * @param mixed|null $default
	 * @return array|mixed
	 */
	public function getPageProps( Title $title, $prop = null, $default = null ) {
		if ( $this->pageProps === null ) {
			$this->pageProps = MediaWikiServices::getInstance()->getService(
				'BSUtilityFactory'
			)->getPagePropHelper( $title )->getPageProps();
		}

		if ( $prop !== null ) {
			return isset( $this->pageProps[$prop] ) ? $this->pageProps[$prop] : $default;
		}

		return $this->pageProps;
	}

	/**
	 *
	 * @return array
	 */
	protected function getCategories() {
		return array_keys( $this->parserOutput->getCategories() );
	}

	/**

	 * @return string
	 */
	protected function getTextContent() {
		$sText = '';
		if ( $this->content instanceof Content ) {
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

	/**
	 *
	 * @param string $sText
	 * @return string
	 */
	protected function stripTags( $sText ) {
		$sText = strip_tags( $sText );
		$sText = preg_replace( '/<!--(.|\s)*?-->/', '', $sText );
		return trim( $sText );
	}

	/**
	 * Collects all tags that are present on page,
	 * and are also registered with Parser
	 *
	 * @return array
	 */
	protected function getTags() {
		$res = [];

		$registeredTags = MediaWikiServices::getInstance()->getParser()->getTags();
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
	 * @return array
	 */
	protected function parseWikipageForTags() {
		if ( $this->content instanceof Content == false ) {
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

	/**
	 *
	 * @return string
	 */
	protected function getRedirectsTo() {
		if ( !$this->isLatestRevision() || !$this->title->isRedirect() ) {
			return '';
		}

		$redirTitle = $this->wikipage->getRedirectTarget();
		if ( $redirTitle instanceof Title ) {
			return $this->getDisplayTitle( $redirTitle );
		}
		return '';
	}

	/**
	 *
	 * @return string[]
	 */
	protected function getRedirects() {
		$redirs = $this->title->getRedirectsHere();
		$indexable = [];
		foreach ( $redirs as $redirect ) {
			$indexable[] = $redirect->getPrefixedText();
		}

		return $indexable;
	}

	/**
	 *
	 * @return string
	 */
	protected function getDisplayTitle() {
		if ( !$this->isLatestRevision() ) {
			return $this->title->getPrefixedText();
		}
		$displayTitle = $this->getPageProps( $this->title, 'displaytitle' );
		if ( $displayTitle ) {
			return $displayTitle;
		}
		return $this->title->getPrefixedText();
	}

	/**
	 *
	 * @return array
	 */
	protected function getUsedFiles() {
		return array_keys( $this->parserOutput->getImages() );
	}

	/**
	 * @return RevisionRecord|null
	 * @throws FatalError
	 * @throws MWException
	 */
	protected function getRevision() {
		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle(
			$this->title
		);
		Hooks::run( 'BSExtendedSearchWikipageFetchRevision', [
			$this->title,
			&$revision
		] );

		return $revision;
	}

	/**
	 * @return bool
	 */
	protected function isLatestRevision() {
		return $this->revision->getId() === $this->title->getLatestRevID();
	}

	/**
	 *
	 * @param Title|null $title
	 * @return string
	 */
	protected function getNamespaceText( $title ) {
		if ( !$title instanceof Title ) {
			return '';
		}
		if ( $title->getNamespace() === NS_MAIN ) {
			return wfMessage( 'bs-ns_main' )->plain();
		}
		return $title->getNsText();
	}

	/**
	 * @throws MWException
	 */
	private function assertWikiPage() {
		if ( !$this->wikipage instanceof WikiPageObject ) {
			$exceptionMessage = sprintf(
				'%s: instance of %s expected, %s given',
				__METHOD__, WikiPageObject::class,
				is_null( $this->wikipage ) ? 'null' : get_class( $this->wikipage )
			);
			throw new MWException( $exceptionMessage );
		}
	}

	/**
	 * @throws MWException
	 */
	private function assertRevision() {
		if ( is_null( $this->revision ) ) {
			$exceptionMessage = sprintf(
				'%s: could not retrieve revision for %s',
				__METHOD__, $this->title->getPrefixedDBkey()
			);
			throw new MWException( $exceptionMessage );
		}
	}
}
