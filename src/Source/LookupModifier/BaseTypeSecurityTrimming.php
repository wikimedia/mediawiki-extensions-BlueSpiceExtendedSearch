<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

class BaseTypeSecurityTrimming extends Base {
	/**
	 *
	 * @var \User
	 */
	protected $user;

	/**
	 *
	 * @var array
	 */
	protected $blockedTypes;

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup &$lookup
	 * @param \IContextSource $context
	 */
	public function __construct( &$lookup, $context ) {
		parent::__construct( $lookup, $context );

		$this->user = $context->getUser();
	}

	public function apply() {
		$typesToBlock = [];

		$services = MediaWikiServices::getInstance();
		$backend = $services->getService( 'BSExtendedSearchBackend' );
		foreach ( $backend->getSources() as $key => $source ) {
			$searchPermission = $source->getSearchPermission();
			if ( !$searchPermission ) {
				continue;
			}
			$isAllowed = $services->getPermissionManager()->userHasRight(
				$this->user,
				$searchPermission
			);
			if ( $isAllowed ) {
				continue;
			}
			$typesToBlock[] = $key;
		}

		if ( !empty( $typesToBlock ) ) {
			$this->oLookup->addBoolMustNotTerms( '_type', $typesToBlock );
			$this->blockedTypes = $typesToBlock;
		}
	}

	public function undo() {
		if ( !empty( $this->blockedTypes ) ) {
			$this->oLookup->removeBoolMustNot( '_type' );
		}
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [
			Backend::QUERY_TYPE_AUTOCOMPLETE,
			Backend::QUERY_TYPE_SEARCH
		];
	}
}
