<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class WikiPage extends Base {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig(): array {
		$config = parent::getPropertyConfig();
		return array_merge( $config, [
			'prefixed_title' => [
				'type' => 'text',
				'copy_to' => [ 'congregated', 'suggestions', 'prefixed_title_exact' ],
			],
			'prefixed_title_exact' => [
				'type' => 'keyword'
			],
			'sections' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
			],
			'source_content' => [
				'type' => 'text',
			],
			'rendered_content' => [
				'type' => 'text',
				'copy_to' => 'congregated',
				// required to be able to retrieve highlights
				'store' => true
			],
			'namespace' => [
				'type' => 'integer'
			],
			'namespace_text' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
			],
			'categories' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
			],
			'tags' => [
				'type' => 'keyword'
			],
			'is_redirect' => [
				'type' => 'boolean'
			],
			'redirects_to' => [
				'type' => 'keyword'
			],
			'redirected_from' => [
				'type' => 'text'
			],
			'page_language' => [
				'type' => 'keyword'
			],
			'display_title' => [
				'type' => 'keyword',
				'copy_to' => [ 'congregated', 'suggestions' ]
			],
			'used_files' => [
				'type' => 'keyword'
			]
		] );
	}
}
