<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class SpecialPage extends DecoratorBase {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC += [
			'prefixed_title' => [
				'type' => 'text'
			],
			'description' => [
				'type' => 'text',
				'boost' => 2
			],
			'namespace' => [
				'type' => 'integer',
				'include_in_all' => false
			],
			'namespace_text' => [
				'type' => 'keyword'
			],
		];
		return $aPC;
	}
}