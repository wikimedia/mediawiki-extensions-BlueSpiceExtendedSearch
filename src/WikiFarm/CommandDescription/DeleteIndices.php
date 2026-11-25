<?php

namespace BS\ExtendedSearch\WikiFarm\CommandDescription;

use BlueSpice\WikiFarm\CommandDescriptionBase;

class DeleteIndices extends CommandDescriptionBase {

	/**
	 * @inheritDoc
	 */
	public function getCommandArguments() {
		$maintenancePath = $this->buildMaintenancePath( 'BlueSpiceExtendedSearch' );
		if ( $this->config?->get( 'useUnifiedSearch' ) ) {
			return [
				"$maintenancePath/purgeDocuments.php",
				"--quick"
			];
		}

		return [
			"$maintenancePath/purgeIndexes.php",
			"--quick"
		];
	}

	/**
	 * @return int
	 */
	public function getPosition() {
		return 1000;
	}

	/**
	 * This may take a while
	 * @return bool
	 */
	public function runAsync() {
		return true;
	}

}
