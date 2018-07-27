<?php

namespace BS\ExtendedSearch\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddRelevanceTable extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$dir = $this->getExtensionPath() . '/maintenance/db';

		$this->updater->addExtensionTable(
			'bs_extendedsearch_relevance',
			"$dir/bs_extendedsearch_relevance.sql"
		);
		
		$this->updater->modifyExtensionField( 'bs_extendedsearch_relevance', 'rel_user', "$dir/bs_extendedsearch_relevance.patch.user.sql" );
		$this->updater->modifyExtensionField( 'bs_extendedsearch_relevance', 'rel_result', "$dir/bs_extendedsearch_relevance.patch.result.sql" );
		$this->updater->modifyExtensionField( 'bs_extendedsearch_relevance', 'rel_value', "$dir/bs_extendedsearch_relevance.patch.value.sql" );
		$this->updater->modifyExtensionField( 'bs_extendedsearch_relevance', 'rel_timestamp', "$dir/bs_extendedsearch_relevance.patch.timestamp.sql" );

	}

	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}
