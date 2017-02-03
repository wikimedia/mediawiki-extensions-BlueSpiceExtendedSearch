<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class Base {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		return [
			'uri' => [
				'type' => 'text',
				'include_in_all' => false
			],
			'basename' => [
				'type' => 'text',
				'boost' => 3
			],
			'extension' => [
				'type' => 'keyword'
			],
			'mime_type' => [
				'type' => 'text',
				'include_in_all' => false
			],
			'mtime' => [
				'type' => 'date',
				'include_in_all' => false
			],
			'ctime' => [
				'type' => 'date',
				'include_in_all' => false
			],
			'size' => [
				'type' => 'integer',
				'include_in_all' => false
			],
			'tags' => [
				'type' => 'keyword',
				'boost' => 2
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