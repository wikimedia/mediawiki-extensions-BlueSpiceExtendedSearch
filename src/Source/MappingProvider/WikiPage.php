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
				'copy_to' => [ 'congregated', 'ac_ngram' ],
			],
			'sections' => [
				'type' => 'text',
				'copy_to' => 'congregated'
			],
			'source_content' => [
				'type' => 'text',
				'copy_to' => 'congregated'
			],
			'rendered_content' => [
				'type' => 'text',
				'copy_to' => 'congregated',
				'store' => true //required to be able to retrive highlights
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
			]
		] );

		return $aPC;
	}
}