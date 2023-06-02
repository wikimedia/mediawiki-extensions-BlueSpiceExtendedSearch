<?php

namespace BS\ExtendedSearch;

interface ISearchMappingProvider {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig(): array;

	/**
	 *
	 * @return array
	 */
	public function getSourceConfig(): array;

	/**
	 * Get fields to be sorted on
	 *
	 * @return array
	 */
	public function getSortableFields(): array;
}
