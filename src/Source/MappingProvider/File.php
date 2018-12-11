<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class File extends DecoratorBase {

	/**
	 * We don't need the base64 code in the index, just the extracted data
	 * @see http://stackoverflow.com/questions/29982129/how-to-not-store-attachment-content-using-elastica
	 * @return string
	 */
	public function getSourceConfig() {
		$aSC = $this->oDecoratedMP->getSourceConfig();
		$aSC['excludes'][] = 'the_file';
		return $aSC;
	}

	public function getPropertyConfig() {
		$aPC = $this->oDecoratedMP->getPropertyConfig();
		$aPC = array_merge( $aPC, [
			'the_file' => [
				'type' => 'binary'
			]
		] );

		return $aPC;
	}
}