<?php

namespace BS\ExtendedSearch\SimpleFarmer\CommandDescription;

use BlueSpice\SimpleFarmer\CommandDescriptionBase;

class DeleteIndices extends CommandDescriptionBase {

	/**
	 * @inheritDoc
	 */
	public function getCommandArguments() {
		$maintenancePath = $this->buildMaintenancePath( 'BlueSpiceExtendedSearch' );
		return [
			"$maintenancePath/purgeIndexes.php",
			"--quick"
		];
	}

	/**
	 *
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
