<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Lookup;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentity;

class SubpageContextProvider implements ISearchContextProvider {

	/**
	 * @param NamespaceInfo $namespaceInfo
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly NamespaceInfo $namespaceInfo,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getContextDefinitionForPage( PageIdentity $page, Authority $authority ): ?array {
		if ( !$this->namespaceInfo->hasSubpages( $page->getNamespace() ) ) {
			return null;
		}
		$title = $this->titleFactory->castFromPageIdentity( $page );
		if ( !$title->hasSubpages() ) {
			return null;
		}
		return [
			'namespace' => $title->getNamespace(),
			'basename' => $title->getDBkey(),
			'prefixed' => $title->getPrefixedText()
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getContextDisplayText( array $contextDefinition, UserIdentity $user, Language $language ): Message {
		return Message::newFromKey( 'bs-extendedsearch-context-subpage-label', $contextDefinition['prefixed'] );
	}

	/**
	 * @return bool
	 */
	public function showContextFilterPill(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function applyContext( array $contextDefinition, Authority $actor, Lookup $lookup ) {
		$mainPageQuery = [
			'regexp' => [
				'basename_exact' => $contextDefinition['basename'] . '|' . $contextDefinition['basename'] . '/.*'
			]
		];
		$origMatch = $lookup->getQueryString() ?? null;
		if ( !$origMatch ) {
			return;
		}
		$lookup['query']['bool']['must'] = [
			[ 'query_string' => $origMatch ],
			$mainPageQuery
		];
		$lookup->clearFilter( 'namespace' );
		$lookup->addTermFilter( 'namespace', $contextDefinition['namespace'] );
	}

	/**
	 * @param array $contextDefinition
	 * @param Lookup $lookup
	 * @return void
	 */
	public function undoContext( array $contextDefinition, Lookup $lookup ) {
		$lookup->clearFilter( 'namespace' );
		$origMatch = $lookup->getQueryString() ?? null;
		if ( !$origMatch ) {
			return;
		}
		$lookup['query']['bool']['must'] = [
			[ 'query_string' => $origMatch ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getContextKey(): string {
		return 'subpage';
	}

	/**
	 * @inheritDoc
	 */
	public function getContextPriority(): int {
		return 1;
	}
}
