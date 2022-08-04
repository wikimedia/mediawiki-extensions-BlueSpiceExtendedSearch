<?php

namespace BS\ExtendedSearch\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddRelevanceTable extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$dbType = $this->updater->getDB()->getType();
		$dir = $this->getExtensionPath();

		$this->updater->addExtensionTable(
			'bs_extendedsearch_relevance',
			"$dir/maintenance/db/sql/$dbType/bs_extendedsearch_relevance-generated.sql"
		);

		if ( $dbType == 'mysql' ) {
			$this->updater->modifyExtensionField(
				'bs_extendedsearch_relevance',
				'rel_user',
				"$dir/maintenance/db/bs_extendedsearch_relevance.patch.user.sql"
			);
			$this->updater->modifyExtensionField(
				'bs_extendedsearch_relevance',
				'rel_result',
				"$dir/maintenance/db/bs_extendedsearch_relevance.patch.result.sql" );
			$this->updater->modifyExtensionField(
				'bs_extendedsearch_relevance',
				'rel_value',
				"$dir/maintenance/db/bs_extendedsearch_relevance.patch.value.sql"
			);
			$this->updater->modifyExtensionField(
				'bs_extendedsearch_relevance',
				'rel_timestamp',
				"$dir/maintenance/db/bs_extendedsearch_relevance.patch.timestamp.sql"
			);
		}
	}

	/**
	 *
	 * @return string
	 */
	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
