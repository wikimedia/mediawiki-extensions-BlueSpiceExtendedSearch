<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\ResultRelevance;

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

	public function __construct( &$lookup, $context ) {
		parent::__construct( $lookup, $context );

		$this->user = $context->getUser();
	}

	public function apply() {
		$typesToBlock = [];

		$backend = \BS\ExtendedSearch\Backend::instance();
		foreach( $backend->getSources() as $key => $source ) {
			$searchPermission = $source->getSearchPermission();
			if( !$searchPermission ) {
				continue;
			}
			if( $this->user->isAllowed( $searchPermission ) == false ) {
				$typesToBlock[] = $key;
			}
		}

		if( !empty( $typesToBlock ) ) {
			$this->oLookup->addBoolMustNotTerms( '_type', $typesToBlock );
			$this->blockedTypes = $typesToBlock;
		}
	}

	public function undo() {
		if( !empty( $this->blockedTypes ) ) {
			$this->oLookup->removeBoolMustNot( '_type' );
		}
	}
}
