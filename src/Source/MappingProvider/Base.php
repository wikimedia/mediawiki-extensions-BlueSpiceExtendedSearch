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
				'type' => 'string',
				'include_in_all' => false
			],
			'basename' => [
				'type' => 'string'
			],
			'extension' => [
				'type' => 'string'
			],
			'mime_type' => [
				'type' => 'string'
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
				'type' => 'string'
			],
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getSourceConfig() {
		return [
			'excludes' => [ 'id' ]
		];
	}
}