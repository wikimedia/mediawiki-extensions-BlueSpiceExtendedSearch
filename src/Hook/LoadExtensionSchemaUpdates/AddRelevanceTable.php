<?php

namespace BS\ExtendedSearch\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddRelevanceTable extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$dir = $this->getExtensionPath().'/maintenance/db';

		$this->updater->addExtensionTable(
			'bs_extendedsearch_relevance',
			"$dir/bs_extendedsearch_relevance.sql"
		);
	}

	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
