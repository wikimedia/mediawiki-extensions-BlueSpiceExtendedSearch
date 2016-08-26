<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class Base {
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
}