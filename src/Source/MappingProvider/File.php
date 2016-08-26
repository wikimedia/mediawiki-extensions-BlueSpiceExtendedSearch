<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class File extends DecoratorBase {
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC += [
			'the_file' => [
				'type' => 'attachment'
			]
		];
		return $aPC;
	}
}