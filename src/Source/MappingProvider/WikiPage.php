<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class WikiPage extends DecoratorBase {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC = array_merge( $aPC, [
			'prefixed_title' => [
				'type' => 'text',
				'copy_to' => [ 'congregated', 'ac_ngram', 'prefixed_title_exact' ],
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
				'copy_to' => 'congregated'
			],
			'rendered_content' => [
				'type' => 'text',
				'copy_to' => 'congregated',
				'store' => true // required to be able to retrieve highlights
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
				'copy_to' => [ 'congregated', 'ac_ngram' ]
			],
			'used_files' => [
				'type' => 'keyword'
			]
		] );

		return $aPC;
	}
}
