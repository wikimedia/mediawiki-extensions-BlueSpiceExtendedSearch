<?php

namespace BS\ExtendedSearch\Source;

class RepoFiles extends DecoratorBase {

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\RepoFile
	 */
	public function getCrawler() {
		return new Crawler\RepoFile( $this->getConfig() );
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

	public function getFormatter() {
		return new Formatter\FileFormatter( $this );
	}
}