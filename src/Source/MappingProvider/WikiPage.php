<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class WikiPage extends DecoratorBase {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC += [
			'prefixed_title' => [
				'type' => 'text',
				'copy_to' => 'congregated'
			],
			'sections' => [
				'type' => 'text',
				'boost' => 2,
				'copy_to' => 'congregated'
			],
			'source_content' => [
				'type' => 'text',
				'boost' => 2,
				'copy_to' => 'congregated'
			],
			'rendered_content' => [
				'type' => 'text',
				'boost' => 2,
				'copy_to' => 'congregated'
			],
			'namespace' => [
				'type' => 'integer'
			],
			'namespace_text' => [
				'type' => 'keyword',
				'copy_to' => 'congregated'
			],
		];
		return $aPC;
	}
}