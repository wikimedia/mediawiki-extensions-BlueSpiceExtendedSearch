<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Lookup;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\UserIdentity;

interface ISearchContextProvider extends ISearchPlugin {

	/**
	 * @param PageIdentity $page
	 * @param Authority $authority
	 * @return array|null
	 */
	public function getContextDefinitionForPage( PageIdentity $page, Authority $authority ): ?array;

	/**
	 * @param array $contextDefinition
	 * @param UserIdentity $user
	 * @param Language $language
	 * @return Message
	 */
	public function getContextDisplayText( array $contextDefinition, UserIdentity $user, Language $language ): Message;

	/**
	 * Whether filter this context applies requires a non-standard filter pill
	 * @return bool
	 */
	public function showContextFilterPill(): bool;

	/**
	 * @param array $contextDefinition
	 * @param Authority $actor
	 * @param Lookup $lookup
	 * @return mixed
	 */
	public function applyContext( array $contextDefinition, Authority $actor, Lookup $lookup );

	/**
	 * Un-apply any context modifications made by applyContext
	 * @param array $contextDefinition
	 * @param Lookup $lookup
	 * @return mixed
	 */
	public function undoContext( array $contextDefinition, Lookup $lookup );

	/**
	 * @return string
	 */
	public function getContextKey(): string;

	/**
	 * @return int
	 */
	public function getContextPriority(): int;
}
