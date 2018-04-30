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
			'ac_suggest' => [
				'type' => 'completion',
				'preserve_position_increments' => false,
				'contexts' => [
					[
						'name' => 'namespace',
						'type' => 'category',
						'path' => 'namespace_text'
					]
				]
			],
			'prefixed_title' => [
				'type' => 'text',
				'copy_to' => 'congregated',
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
			]
		] );

		return $aPC;
	}
}