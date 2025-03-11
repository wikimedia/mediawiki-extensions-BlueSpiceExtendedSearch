<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Lookup;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\UserIdentity;

class NullContextProvider implements ISearchContextProvider {

	/**
	 * @inheritDoc
	 */
	public function getContextDefinitionForPage( PageIdentity $page, Authority $authority ): ?array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function showContextFilterPill(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getContextDisplayText( array $contextDefinition, UserIdentity $user, Language $language ): Message {
		return Message::newFromKey( 'bs-extendedsearch-autocomplete-context-none' );
	}

	/**
	 * @inheritDoc
	 */
	public function applyContext( array $contextDefinition, Authority $actor, Lookup $lookup ) {
		// NOOP
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
		return 'none';
	}

	/**
	 * @inheritDoc
	 */
	public function getContextPriority(): int {
		return 90;
	}
}
