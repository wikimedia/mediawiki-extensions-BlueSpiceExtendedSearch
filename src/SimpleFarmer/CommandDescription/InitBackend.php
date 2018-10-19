<?php

namespace BS\ExtendedSearch\SimpleFarmer\CommandDescription;

use BlueSpice\SimpleFarmer\CommandDescriptionBase;

class InitBackend extends CommandDescriptionBase {

	/**
	 *
	 * @return string[]
	 */
	public function getCommandArguments() {
		$maintenancePath = $this->buildMaintenancePath( 'BlueSpiceExtendedSearch' );
		$args = [
			"$maintenancePath/initBackends.php",
			"--quick"
		];
		return $args;
	}

	/**
	 *
	 * @return int
	 */
	public function getPosition() {
		//Must be executed _before "rebuildIndex.php"
		return 60;
	}

}