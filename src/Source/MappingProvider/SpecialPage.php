<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class SpecialPage extends Base {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig(): array {
		$config = parent::getPropertyConfig();
		return array_merge( $config, [
			'prefixed_title' => [
				'type' => 'text',
				'copy_to' => [ 'congregated' ]
			],
			'description' => [
				'type' => 'text',
				'copy_to' => 'congregated',
			],
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
