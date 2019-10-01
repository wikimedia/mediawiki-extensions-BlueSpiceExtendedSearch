<?php

namespace BS\ExtendedSearch\Data\SearchHistory;

use BlueSpice\Data\DatabaseWriter;

class Writer extends DatabaseWriter {

	/**
	 *
	 * @return array
	 */
	protected function getIdentifierFields() {
		return [ Record::ID ];
	}

	/**
	 *
	 * @return string
	 */
	protected function getTableName() {
		return Schema::TABLE_NAME;
	}

	/**
	 *
	 * @return Schema
	 */
	public function getSchema() {
		return new Schema;
	}

}
