<?php

namespace BS\ExtendedSearch\SimpleFarmer\CommandDescription;

use BlueSpice\SimpleFarmer\CommandDescriptionBase;

class RebuildIndex extends CommandDescriptionBase {

	/**
	 *
	 * @return string[]
	 */
	public function getCommandArguments() {
		$maintenancePath = $this->buildMaintenancePath( 'BlueSpiceExtendedSearch' );
		$args = [
			"$maintenancePath/rebuildIndex.php",
			"--quick"
		];
		return $args;
	}

	/**
	 *
	 * @return int
	 */
	public function getPosition() {
		//Must be executed _after_ "initBackend.php"
		return 70;
	}

	/**
	 * This may take some time
	 * @return bool
	 */
	public function runAsync() {
		return true;
	}
}