<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\ResultRelevance;
use MediaWiki\Context\IContextSource;

class BaseUserRelevance extends LookupModifier {
	/** @var array */
	protected $relevanceValues = [];
	/** @var array */
	protected $positiveBoosts = [];
	/** @var array */
	protected $negativeBoosts = [];

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup &$lookup
	 * @param IContextSource $context
	 */
	public function __construct( &$lookup, $context ) {
		parent::__construct( $lookup, $context );

		$resultRelevanceManager = new ResultRelevance(
			$context->getUser()
		);
		$this->relevanceValues = $resultRelevanceManager->getAllValuesForUser();
	}

	public function apply() {
		if ( $this->context->getUser()->isRegistered() ) {
			return;
		}
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
			$this->lookup->addShouldTerms( '_id', $this->positiveBoosts, 2, false );
		}

		if ( !empty( $this->negativeBoosts ) ) {
			$this->lookup->addShouldTerms( '_id', $this->negativeBoosts, -3, false );
		}
	}

	public function undo() {
		if ( $this->relevanceValues == [] ) {
			return;
		}

		$this->lookup->removeShouldTerms( '_id', array_merge( $this->positiveBoosts, $this->negativeBoosts ) );
	}

}
