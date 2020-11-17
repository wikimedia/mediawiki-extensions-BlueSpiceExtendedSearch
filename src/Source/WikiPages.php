<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\PostProcessor\WikiPage as WikiPagePostProcessor;

class WikiPages extends DecoratorBase {

	/**
	 * @param Base $base
	 * @return WikiPages
	 */
	public static function create( $base ) {
		return new self( $base );
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

	/**
	 *
	 * @return Formatter\WikiPageFormatter
	 */
	public function getFormatter() {
		return new Formatter\WikiPageFormatter( $this );
	}

	/**
	 *
	 * @return string
	 */
	public function getSearchPermission() {
		return 'extendedsearch-search-wikipage';
	}

	/**
	 * @inheritDoc
	 */
	public static function getPostProcessor( $base ) {
		return WikiPagePostProcessor::factory( $base );
	}

}
