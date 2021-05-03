<?php

namespace BS\ExtendedSearch;

class ResultRelevance {
	/**
	 *
	 * @var \User
	 */
	protected $user;

	/**
	 * ID of result in Elastic's index
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

	/**
	 *
	 * @param \User $user
	 * @param string $resultId
	 * @param int $value
	 */
	public function __construct( \User $user, $resultId = '', $value = 0 ) {
		$this->user = $user;
		$this->resultId = $resultId;
		$this->value = is_int( $value ) ? $value : 0;
	}

	/**
	 * Gets all marked results by user
	 *
	 * @return array
	 */
	public function getAllValuesForUser() {
		$result = wfGetDB( DB_REPLICA )->select(
			'bs_extendedsearch_relevance',
			[ 'esr_result', 'esr_value' ],
			$this->queryConditions
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
	 * @return int
	 */
	public function getValue() {
		if ( $this->resultId == '' ) {
			return 0;
		}

		$this->setConditions();

		$result = wfGetDB( DB_REPLICA )->selectRow(
			'bs_extendedsearch_relevance',
			[ 'esr_value' ],
			$this->queryConditions
		);

		if ( $result == null ) {
			return 0;
		}

		return $result->esr_value;
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

		$dbw = wfGetDB( DB_PRIMARY );
		$this->setConditions();

		if ( $this->value == 0 ) {
			$result = $dbw->delete(
				'bs_extendedsearch_relevance',
				$this->queryConditions
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
					]
				);
			} else {
				$result = $dbw->update(
					'bs_extendedsearch_relevance',
					[
						'esr_value' => $this->value,
						'esr_timestamp' => wfTimestamp( TS_UNIX )
					],
					$this->queryConditions
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
