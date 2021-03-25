<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class Base {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		return [
			'sortable_id' => [
				'type' => 'keyword',
				'doc_values' => true
			],
			'congregated' => [
				'type' => 'text'
			],
			'ac_ngram' => [
				'type' => 'text',
				'analyzer' => 'autocomplete',
				'search_analyzer' => 'standard'
			],
			'uri' => [
				'type' => 'text'
			],
			'basename' => [
				'type' => 'text',
				'copy_to' => [ 'congregated', 'ac_ngram' ],
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
	public function getSourceConfig() {
		return [];
	}

	/**
	 * Get fields to be sorted on
	 *
	 * @return array
	 */
	final public function getSortableFields() {
		return [
			'basename',
			'mtime',
			'ctime',
			'size'
		];
	}
}
