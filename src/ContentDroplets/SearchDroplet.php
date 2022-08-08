<?php

declare( strict_types = 1 );

namespace BS\ExtendedSearch\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use Message;
use RawMessage;

class SearchDroplet extends TagDroplet {

	/**
	 */
	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return new RawMessage( 'Search' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return new RawMessage( "Insert search" );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'search';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModule(): string {
		return 'ext.blueSpiceExtendedSearch.TagSearch';
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
