<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Lookup;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\User\UserIdentity;

class NamespaceContextProvider implements ISearchContextProvider {

	/**
	 * @param NamespaceInfo $namespaceInfo
	 */
	public function __construct(
		private readonly NamespaceInfo $namespaceInfo
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getContextDefinitionForPage( PageIdentity $page, Authority $authority ): ?array {
		if ( !$this->namespaceInfo->isSubject( $page->getNamespace() ) ) {
			return null;
		}
		return [
			'namespace' => $page->getNamespace(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function showContextFilterPill(): bool {
		// Take care of by the generic `namespace` filter
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getContextDisplayText( array $contextDefinition, UserIdentity $user, Language $language ): Message {
		if ( $contextDefinition['namespace'] === NS_MAIN ) {
			$nsText = Message::newFromKey( 'blanknamespace' )->text();
		} else {
			$nsText = $language->getNsText( $contextDefinition['namespace'] );
		}
		return Message::newFromKey( 'bs-extendedsearch-context-namespace-label', $nsText );
	}

	/**
	 * @inheritDoc
	 */
	public function applyContext( array $contextDefinition, Authority $actor, Lookup $lookup ) {
		if ( !isset( $contextDefinition['namespace'] ) ) {
			return;
		}
		$lookup->clearFilter( 'namespace' );
		$lookup->addTermFilter( 'namespace', $contextDefinition['namespace'] );
	}

	/**
	 * @param array $contextDefinition
	 * @param Lookup $lookup
	 * @return void
	 */
	public function undoContext( array $contextDefinition, Lookup $lookup ) {
		// NOOP
	}

	/**
	 * @inheritDoc
	 */
	public function getContextKey(): string {
		return 'namespace';
	}

	/**
	 * @inheritDoc
	 */
	public function getContextPriority(): int {
		return 10;
	}
}
