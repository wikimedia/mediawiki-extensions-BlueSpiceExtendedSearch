<?php

namespace BS\ExtendedSearch;

class PluginManager {

	/**
	 * @param array $plugins
	 */
	public function __construct(
		private readonly array $plugins
	) {
	}

	/**
	 * @return array
	 */
	public function getPlugins(): array {
		return $this->plugins;
	}

	/**
	 * @param string $interface
	 * @return array
	 */
	public function getPluginsImplementing( string $interface ): array {
		$plugins = [];
		foreach ( $this->plugins as $plugin ) {
			if ( $plugin instanceof $interface ) {
				$plugins[] = $plugin;
			}
		}
		return $plugins;
	}
}
