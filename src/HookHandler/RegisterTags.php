<?php

namespace BS\ExtendedSearch\HookHandler;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Tag\TagSearch;
use MediaWiki\Language\Language;
use MediaWiki\Title\NamespaceInfo;
use MWStake\MediaWiki\Component\GenericTagHandler\Hook\MWStakeGenericTagHandlerInitTagsHook;

class RegisterTags implements MWStakeGenericTagHandlerInitTagsHook {

	/**
	 * @param Backend $backend
	 * @param NamespaceInfo $namespaceInfo
	 * @param Language $language
	 */
	public function __construct(
		private readonly Backend $backend,
		private readonly NamespaceInfo $namespaceInfo,
		private readonly Language $language
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeGenericTagHandlerInitTags( array &$tags ) {
		$tags[] = new TagSearch( $this->backend, $this->namespaceInfo, $this->language );
	}
}
