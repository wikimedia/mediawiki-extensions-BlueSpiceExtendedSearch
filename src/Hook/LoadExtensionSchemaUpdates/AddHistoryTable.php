<?php

namespace BS\ExtendedSearch\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddHistoryTable extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$dbType = $this->updater->getDB()->getType();
		$dir = $this->getExtensionPath();

		$this->updater->addExtensionTable(
			'bs_extendedsearch_history',
			"$dir/maintenance/db/sql/$dbType/bs_extendedsearch_history-generated.sql"
		);
	}

	/**
	 *
	 * @return string
	 */
	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
