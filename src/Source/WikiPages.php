<?php

namespace BS\ExtendedSearch\Source;

class WikiPages extends DecoratorBase {
	/**
	 *
	 * @return string
	 */
	public function getTypeKey() {
		return 'wikipage';
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\WikiPage
	 */
	public function getCrawler() {
		return new Crawler\WikiPage( $this->getConfig() );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\WikiPage
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\WikiPage(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\MappingProvider\WikiPage
	 */
	public function getMappingProvider() {
		return new MappingProvider\WikiPage(
			$this->oDecoratedSource->getMappingProvider()
		);
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Updater\WikiPage
	 */
	public function getUpdater() {
		return new Updater\WikiPage( $this->oDecoratedSource );
	}
}