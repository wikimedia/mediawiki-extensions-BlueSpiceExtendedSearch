<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class Base {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		return [
			'congregated' => [
				'type' => 'text'
			],
			'ac_suggest' => [
				'type' => 'completion',
				'preserve_position_increments' => false
			],
			'uri' => [
				'type' => 'text'
			],
			'basename' => [
				'type' => 'text',
				'boost' => 3,
				'copy_to' => [ 'congregated', 'ac_suggest' ],
				'fielddata' => true //required in order to be sortable
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
				'boost' => 2,
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