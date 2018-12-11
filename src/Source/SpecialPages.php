<?php

namespace BS\ExtendedSearch\Source;

class SpecialPages extends DecoratorBase {
	/**
	 * @param Base $base
	 * @return SpecialPages
	 */
	public static function create( $base ) {
		return new self( $base );
	}

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

	public function getSearchPermission() {
		return 'extendedsearch-search-specialpage';
	}
}