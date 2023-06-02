<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class RepoFile extends File {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig(): array {
		$config = parent::getPropertyConfig();
		return array_merge( $config, [
			'namespace' => [
				'type' => 'integer'
			],
			'namespace_text' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
			],
		] );
	}
}
