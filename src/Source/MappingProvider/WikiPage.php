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
				'type' => 'string'
			],
			'sections' => [
				'type' => 'string'
			],
			'source_content' => [
				'type' => 'string'
			],
			'rendered_content' => [
				'type' => 'string'
			],
			'namespace' => [
				'type' => 'integer',
				'include_in_all' => false
			],
			'namespace_text' => [
				'type' => 'string'
			],
		];
		return $aPC;
	}
}