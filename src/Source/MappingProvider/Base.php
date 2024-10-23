<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

use BS\ExtendedSearch\ISearchMappingProvider;

class Base implements ISearchMappingProvider {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig(): array {
		return [
			'id' => [
				'type' => 'text'
			],
			'sortable_id' => [
				'type' => 'keyword',
				'doc_values' => true
			],
			'congregated' => [
				'type' => 'text'
			],
			'suggestions' => [
				'type' => 'text',
				'analyzer' => 'autocomplete',
				'search_analyzer' => 'autocomplete'
			],
			'suggestions_extra' => [
				'type' => 'text',
				'analyzer' => 'autocomplete',
				'search_analyzer' => 'autocomplete'
			],
			'uri' => [
				'type' => 'text'
			],
			'basename' => [
				'type' => 'text',
				'copy_to' => [ 'congregated' ],
				// required in order to be sortable
				'fielddata' => true
			],
			'basename_exact' => [
				'type' => 'keyword'
			],
			'extension' => [
				'type' => 'keyword',
				'copy_to' => 'congregated',
				'normalizer' => 'lowercase'
			],
			'mime_type' => [
				'type' => 'text'
			],
			'mtime' => [
				'type' => 'date'
			],
			'ctime' => [
				'type' => 'date'
			],
			'size' => [
				'type' => 'integer'
			],
			'tags' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
			],
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getSourceConfig(): array {
		return [];
	}

	/**
	 * Get fields to be sorted on
	 *
	 * @return array
	 */
	public function getSortableFields(): array {
		return [
			'basename',
			'mtime',
			'ctime',
			'size'
		];
	}
}
