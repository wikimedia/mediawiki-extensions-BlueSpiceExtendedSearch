<?php

namespace BS\ExtendedSearch\Source;

class SpecialPages extends DecoratorBase {

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\SpecialPage
	 */
	public function getCrawler() {
		return new Crawler\SpecialPage( $this->getConfig() );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\SpecialPage
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\SpecialPage(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 * 
	 */
	public function getMappingProvider() {
		return new MappingProvider\SpecialPage(
			$this->oDecoratedSource->getMappingProvider()
		);
	}

	public function getFormatter() {
		return new Formatter\SpecialPageFormatter( $this );
	}
}