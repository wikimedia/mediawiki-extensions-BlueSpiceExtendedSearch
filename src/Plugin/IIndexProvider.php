<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\Backend;

interface IIndexProvider extends ISearchPlugin {

	/**
	 * @param Backend $backend
	 * @param array|null $limitToSources
	 * @param array &$indices
	 * @return void
	 */
	public function setIndices(
		Backend $backend, ?array $limitToSources, array &$indices
	): void;

	/**
	 * @param string $index
	 * @param Backend $backend
	 * @return string|null
	 */
	public function typeFromIndexName( string $index, Backend $backend ): ?string;

	/**
	 * @param string $index
	 * @return string|null
	 */
	public function getIndexLabel( string $index ): ?string;
}
