<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class File extends DecoratorBase {
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC += [
			'content' => [
				'type' => 'string'
			]
		];
		return $aPC;
	}
}