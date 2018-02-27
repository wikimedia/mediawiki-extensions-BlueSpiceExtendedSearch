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
		$aPC += [
			'the_file' => [
				'type' => 'attachment',
				'copy_to' => 'congregated'
				/*'fields' => [
					'content' => ['index' => true],
					'title' => ['index' => true],
					'date' => ['store' => true],
					'author' => ['index' => true],
					'keywords' => ['index' => true],
					'content_type' => ['store' => true],
					'content_length' => ['store' => true],
					'language' => ['store' => true]
				]*/
			]
		];
		return $aPC;
	}
}