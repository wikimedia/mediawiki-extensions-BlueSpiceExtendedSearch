<?php

namespace BS\ExtendedSearch;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;

class ResultRelevance {
	/**
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * ID of result in OS's index
	 *
	 * @var string
	 */
	protected $resultId;

	/**
	 * Relevance to the user. Can be:
	 * 1 - Is relevant
	 * 0 - Indifferent
	 * -1 - Not relevant
	 *
	 * This var is int to allow for multi-grading
	 * of relevance in the future
	 *
	 * @var int
	 */
	protected $value;

	/**
	 *
	 * @var array
	 */
	protected $queryConditions;

	/** @var MediaWikiServices */
	protected $services;

	/**
	 *
	 * @param User $user
	 * @param string $resultId
	 * @param bool $value
	 */
	public function __construct( User $user, $resultId = '', $value = false ) {
		$this->user = $user;
		$this->resultId = $resultId;
		$this->value = $value;
		$this->services = MediaWikiServices::getInstance();
	}

	/**
	 * Gets all marked results by user
	 *
	 * @return array
	 */
	public function getAllValuesForUser() {
		$dbr = $this->services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$result = $dbr->select(
			'bs_extendedsearch_relevance',
			[ 'esr_result', 'esr_value' ],
			$this->queryConditions,
			__METHOD__
		);

		$values = [];
		foreach ( $result as $row ) {
			$values[$row->esr_result] = $row->esr_value;
		}

		return $values;
	}

	/**
	 * Gets relevance for set user and result ID
	 *
	 * @return bool
	 */
	public function getValue() {
		if ( $this->resultId == '' ) {
			return false;
		}

		$this->setConditions();

		$dbr = $this->services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$result = $dbr->selectRow(
			'bs_extendedsearch_relevance',
			[ 'esr_value' ],
			$this->queryConditions,
			__METHOD__
		);

		if ( $result == null ) {
			return false;
		}

		return (bool)$result->esr_value;
	}

	/**
	 * Saves current settings to DB
	 *
	 * @return bool false on failure
	 */
	public function save() {
		if ( $this->resultId == '' ) {
			return false;
		}

		$dbw = $this->services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$this->setConditions();

		if ( $this->value == 0 ) {
			$result = $dbw->delete(
				'bs_extendedsearch_relevance',
				$this->queryConditions,
				__METHOD__
			);
		} else {
			if ( $this->getValue() == 0 ) {
				$result = $dbw->insert(
					'bs_extendedsearch_relevance',
					[
						'esr_user' => $this->user->getId(),
						'esr_result' => $this->resultId,
						'esr_value' => $this->value,
						'esr_timestamp' => wfTimestamp( TS_UNIX )
					],
					__METHOD__
				);
			} else {
				$result = $dbw->update(
					'bs_extendedsearch_relevance',
					[
						'esr_value' => $this->value,
						'esr_timestamp' => wfTimestamp( TS_UNIX )
					],
					$this->queryConditions,
					__METHOD__
				);
			}
		}

		return $result;
	}

	protected function setConditions() {
		$this->queryConditions = [
			'esr_user' => $this->user->getId()
		];
		if ( $this->resultId ) {
			$this->queryConditions['esr_result'] = $this->resultId;
		}
	}
}
