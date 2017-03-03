<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\LookupModifier\WikiPageNamespaceTextAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageUserPreferences;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageNamespacePrefixResolver;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageSecurityTrimming;

class WikiPages extends DecoratorBase {

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
	 * @param \BS\ExtendedSearch\Lookup
	 * @param \IContextSource $oContext
	 * @return BS\ExtendedSearch\Source\LookupModifier\Base[]
	 */
	public function getLookupModifiers( $oLookup, $oContext ) {
		return [
			'wikipage-namespacetextaggregation' => new WikiPageNamespaceTextAggregation( $oLookup, $oContext ),
			'wikipage-userpreferences' => new WikiPageUserPreferences( $oLookup, $oContext ),
			'wikipage-namespaceprefixresolver' => new WikiPageNamespacePrefixResolver( $oLookup, $oContext ),
			'wikipage-securitytrimming' => new WikiPageSecurityTrimming( $oLookup, $oContext )
		];
	}
}