<?php

declare( strict_types = 1 );

namespace BS\ExtendedSearch\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use MediaWiki\Message\Message;

class SearchDroplet extends TagDroplet {

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'bs-extendedsearch-droplet-search-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'bs-extendedsearch-droplet-search-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'droplet-tag-search';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'ext.blueSpiceExtendedSearch.TagSearch' ];
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'navigation', 'content' ];
	}

	/**
	 * @return string
	 */
	protected function getTagName(): string {
		return 'bs:tagsearch';
	}

	/**
	 * @return array
	 */
	protected function getAttributes(): array {
		return [];
	}

	/**
	 * @return bool
	 */
	protected function hasContent(): bool {
		return true;
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'tagsearchCommand';
	}
}
