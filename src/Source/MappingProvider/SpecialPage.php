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
				'type' => 'string'
			],
			'description' => [
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