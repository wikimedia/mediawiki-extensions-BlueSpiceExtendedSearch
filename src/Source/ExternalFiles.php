<?php

namespace BS\ExtendedSearch\Source;

class ExternalFiles extends DecoratorBase {

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\ExternalFile
	 */
	public function getCrawler() {
		return new Crawler\ExternalFile( $this->getConfig() );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\File
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\File(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\MappingProvider\File
	 */
	public function getMappingProvider() {
		return new MappingProvider\File(
			$this->oDecoratedSource->getMappingProvider()
		);
	}
}