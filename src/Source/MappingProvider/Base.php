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
				'fielddata' => true //required in order to be sortable
			],
			'basename_exact' => [
				'type' => 'keyword'
			],
			'extension' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
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
}
