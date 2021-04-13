<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class RepoFile extends File {

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC = array_merge( $aPC, [
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
