<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\ResultRelevance;

class BaseUserRelevance extends Base {
	/** @var array */
	protected $relevanceValues = [];
	/** @var array */
	protected $positiveBoosts = [];
	/** @var array */
	protected $negativeBoosts = [];

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup &$lookup
	 * @param \IContextSource $context
	 * @return BaseUserRelevance
	 */
	public function __construct( &$lookup, $context ) {
		parent::__construct( $lookup, $context );

		if ( $context->getUser()->isRegistered() == false ) {
			return;
		}

		$resultRelevanceManager = new ResultRelevance(
			$context->getUser()
		);
		$this->relevanceValues = $resultRelevanceManager->getAllValuesForUser();
	}

	public function apply() {
		if ( $this->relevanceValues == [] ) {
			return;
		}

		foreach ( $this->relevanceValues as $resultId => $value ) {
			if ( $value == 0 ) {
				continue;
			}
			if ( $value > 0 ) {
				$this->positiveBoosts[] = $resultId;
			} else {
				$this->negativeBoosts[] = $resultId;
			}
		}

		if ( !empty( $this->positiveBoosts ) ) {
			$this->oLookup->addShouldTerms( '_id', $this->positiveBoosts, 2, false );
		}

		if ( !empty( $this->negativeBoosts ) ) {
			$this->oLookup->addShouldTerms( '_id', $this->negativeBoosts, -3, false );
		}
	}

	public function undo() {
		if ( $this->relevanceValues == [] ) {
			return;
		}

		$this->oLookup->removeShouldTerms( '_id', array_merge( $this->positiveBoosts, $this->negativeBoosts ) );
	}

}
