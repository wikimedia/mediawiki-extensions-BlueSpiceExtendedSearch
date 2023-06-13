<?php

namespace BS\ExtendedSearch\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddTraceTable extends LoadExtensionSchemaUpdates {
	/**
	 * @return void
	 */
	protected function doProcess() {
		$dbType = $this->updater->getDB()->getType();
		$dir = $this->getExtensionPath();

		$this->updater->addExtensionTable(
			'bs_extendedsearch_trace',
			"$dir/maintenance/db/sql/$dbType/bs_extendedsearch_trace-generated.sql"
		);
	}

	/**
	 *
	 * @return string
	 */
	protected function getExtensionPath() {
		return dirname( __DIR__, 3 );
	}
}
