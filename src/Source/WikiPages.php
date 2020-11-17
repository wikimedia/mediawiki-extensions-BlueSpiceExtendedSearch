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

	/**
	 * @return array [ 'type' => [ 'modifierName1', 'modifierName2' ] ]
	 */
	protected function getAvailableLookupModifiers() {
		return array_merge_recursive(
			parent::getAvailableLookupModifiers(),
			[
				Backend::QUERY_TYPE_SEARCH => [
					'wikipage-namespacetextaggregation',
					'wikipage-userpreferences',
					'wikipage-namespaceprefixresolver',
					'wikipage-securitytrimming',
					'wikipage-categoriesaggregation',
					'wikipage-renderedcontenthighlight',
					'wikipage-qssourcefields',
					'wikipage-boosters',
					'wikipage-wildcarder',
					'wikipage-unwanted',
					'wikipage-pagelangaggregation',
					'wikipage-langfilter',
				],
				Backend::QUERY_TYPE_AUTOCOMPLETE => [
					'wikipage-securitytrimming',
					'wikipage-acsourcefields',
					'wikipage-boosters',
					'wikipage-acunwanted',
					'wikipage-userpreferences',
				],
			]
		);
	}
}
