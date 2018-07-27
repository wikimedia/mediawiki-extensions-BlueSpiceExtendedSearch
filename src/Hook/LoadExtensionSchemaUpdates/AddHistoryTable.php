<?php

namespace BS\ExtendedSearch\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddHistoryTable extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$dir = $this->getExtensionPath() . '/maintenance/db';

		$this->updater->addExtensionTable(
			'bs_extendedsearch_history',
			"$dir/bs_extendedsearch_history.sql"
		);
	}

	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
