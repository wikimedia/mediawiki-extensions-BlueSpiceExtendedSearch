<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentRenderer;
use MediaWiki\Content\TextContent;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionRenderer;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWException;
use WikiPage as WikiPageObject;

class WikiPage extends Base {

	/** @var HookContainer */
	protected $hookContainer;
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
	/** @var array|null */
	protected $pageProps = null;
	/** @var PageProps */
	protected $pagePropsProvider;
	/** @var Parser */
	protected $parser;
	/** @var RedirectLookup */
	protected $redirectLookup;
	/** @var UserFactory */
	protected $userFactory;

	/** @var ContentRenderer */
	protected $contentRenderer;
	/** @var RevisionLookup */
	protected $revisionLookup;
	/** @var RevisionRenderer */
	private $revisionRenderer;

	/**
	 * @param HookContainer $hookContainer
	 * @param ContentRenderer $contentRenderer
	 * @param RevisionLookup $revisionLookup
	 * @param PageProps $pageProps
	 * @param Parser $parser
	 * @param RedirectLookup $redirectLookup
	 * @param UserFactory $userFactory
	 * @param RevisionRenderer|null $revisionRenderer
	 */
	public function __construct(
		HookContainer $hookContainer, ContentRenderer $contentRenderer, RevisionLookup $revisionLookup,
		PageProps $pageProps, Parser $parser, RedirectLookup $redirectLookup, UserFactory $userFactory,
		?RevisionRenderer $revisionRenderer = null
	) {
		$this->hookContainer = $hookContainer;
		$this->contentRenderer = $contentRenderer;
		$this->revisionLookup = $revisionLookup;
		$this->pagePropsProvider = $pageProps;
		$this->parser = $parser;
		$this->redirectLookup = $redirectLookup;
		$this->userFactory = $userFactory;
		$this->revisionRenderer = $revisionRenderer ?? MediaWikiServices::getInstance()->getRevisionRenderer();
	}

	/**
	 * @inheritDoc
	 */
	public function getDocumentData( $sUri, string $documentId, $wikiPage ): array {
		$aDC = parent::getDocumentData( $sUri, $documentId, null );
		$this->wikipage = $wikiPage;
		$this->assertWikiPage();
		$this->title = $this->wikipage->getTitle();
		$this->revision = $this->getRevision();
		$this->assertRevision();

		$this->content = $this->revision->getContent( 'main' );
		$this->parserOutput = $this->contentRenderer->getParserOutput( $this->content, $this->title );

		$firstRev = $this->revisionLookup->getFirstRevision( $this->title->toPageIdentity() );

		if ( $firstRev === null ) {
			return $aDC;
		}

		$aDC = array_merge( $aDC, [
			'uri' => $this->title->getFullURL(),
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
				$firstRev->getTimestamp()
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
			'used_files' => $this->getUsedFiles(),
			'page_id' => $this->title->getArticleID(),
			'suggestions' => $this->title->getPrefixedText(),
			'suggestions_extra' => $this->getDisplayTitle(),
		] );

		return $aDC;
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
			$pageProps = $this->pagePropsProvider->getAllProperties( $title );
			$this->pageProps = $pageProps[$title->getArticleID()] ?? [];
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
		$renderedRevision = $this->revisionRenderer->getRenderedRevision( $this->revision );
		$cats = $renderedRevision->getRevisionParserOutput()->getCategoryNames();

		$categories = [];
		foreach ( $cats as $cat ) {
			$categories[] = Title::newFromDBkey( $cat )->getText();
		}

		return $categories;
	}

	/**
	 *
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
		$html = $this->parserOutput->runOutputPipeline( \ParserOptions::newFromAnon(), [
			'allowTOC' => false,
			'enableSectionEditLinks' => false
		] )->getRawText() ?? '';
		return $this->stripTags( $html );
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
		// Replace whole `<styles>...</styles>` blocks with a space
		$sText = preg_replace( '/<style[^>]*>.*?<\/style>/is', ' ', $sText );
		$sText = strip_tags( $sText );
		$sText = preg_replace( '/<!--(.|\s)*?-->/', '', $sText );
		// Replace all variables with a space
		$sText = preg_replace( '/\{\{[^}]*\}\}/', ' ', $sText );
		$sText = preg_replace( '/__[^_]*__/', ' ', $sText );
		// Remove excess line breaks and empty lines
		$sText = preg_replace( '/\n{2,}/', "\n", $sText );
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

		$registeredTags = $this->parser->getTags();
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
		$text = ( $this->content instanceof TextContent ) ? $this->content->getText() : '';
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

		$redirTarget = $this->redirectLookup->getRedirectTarget( $this->wikipage );
		$redirTitle = Title::castFromLinkTarget( $redirTarget );
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
	 * @param Title|null $title
	 * @return string
	 */
	protected function getDisplayTitle( $title = null ) {
		if ( !$title && !$this->isLatestRevision() ) {
			return $this->title->getPrefixedText();
		}
		$title = $title ?? $this->title;
		$displayTitle = $this->getPageProps( $title, 'displaytitle' );
		if ( $displayTitle ) {
			return $displayTitle;
		}
		if ( $this->title->getNamespace() === NS_USER ) {
			$user = $this->userFactory->newFromName( $title->getDBkey() );
			if ( $user instanceof User ) {
				if ( $user->isRegistered() && $user->getRealName() !== '' ) {
					return $user->getRealName();
				}
			}
			// Fall back to username
			return $title->getText();
		}
		return $title->getPrefixedText();
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
	 * @throws MWException
	 */
	protected function getRevision() {
		$revision = $this->revisionLookup->getRevisionByTitle( $this->title );
		$this->hookContainer->run(
			'BSExtendedSearchWikipageFetchRevision',
			[ $this->title, &$revision ]
		);

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
			return wfMessage( 'bs-ns_main' )->text();
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
				$this->wikipage === null ? 'null' : get_class( $this->wikipage )
			);
			throw new MWException( $exceptionMessage );
		}
	}

	/**
	 * @throws MWException
	 */
	private function assertRevision() {
		if ( $this->revision === null ) {
			$exceptionMessage = sprintf(
				'%s: could not retrieve revision for %s',
				__METHOD__, $this->title->getPrefixedDBkey()
			);
			throw new MWException( $exceptionMessage );
		}
	}
}
