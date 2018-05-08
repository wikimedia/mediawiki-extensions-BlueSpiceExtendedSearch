<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class SpecialPage extends DecoratorBase {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC = array_merge( $aPC, [
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
		return $aPC;
	}
}