<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\LookupModifier\WikiPageNamespaceTextAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageCategoriesAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageUserPreferences;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageNamespacePrefixResolver;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageSecurityTrimming;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageRenderedContentHighlight;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageAutocompleteSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageBoosters;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageAutocompleteRemoveUnwanted;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageSimpleQSFields;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageQSSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageWildcarder;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageRemoveUnwanted;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageLanguageAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageLanguageFilter;
use BS\ExtendedSearch\Source\LookupModifier\Base as LookupModifier;

class WikiPages extends DecoratorBase {
	protected $lookupModifiers = [
		LookupModifier::TYPE_SEARCH => [
			'wikipage-namespacetextaggregation' => WikiPageNamespaceTextAggregation::class,
			'wikipage-userpreferences' => WikiPageUserPreferences::class,
			'wikipage-namespaceprefixresolver' => WikiPageNamespacePrefixResolver::class,
			'wikipage-securitytrimming' => WikiPageSecurityTrimming::class,
			'wikipage-categoriesaggregation' => WikiPageCategoriesAggregation::class,
			'wikipage-renderedcontenthighlight' => WikiPageRenderedContentHighlight::class,
			'wikipage-qssourcefields' => WikiPageQSSourceFields::class,
			'wikipage-boosters' => WikiPageBoosters::class,
			'wikipage-wildcarder' => WikiPageWildcarder::class,
			'wikipage-unwanted' => WikiPageRemoveUnwanted::class,
			'wikipage-pagelangaggregation' => WikiPageLanguageAggregation::class,
			'wikipage-langfilter' => WikiPageLanguageFilter::class
		],
		LookupModifier::TYPE_AUTOCOMPLETE => [
			'wikipage-securitytrimming' => WikiPageSecurityTrimming::class,
			'wikipage-acsourcefields' => WikiPageAutocompleteSourceFields::class,
			'wikipage-boosters' => WikiPageBoosters::class,
			'wikipage-acunwanted' => WikiPageAutocompleteRemoveUnwanted::class,
			'wikipage-userpreferences' => WikiPageUserPreferences::class
		]
	];

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

	public function getFormatter() {
		return new Formatter\WikiPageFormatter( $this );
	}

	public function getSearchPermission() {
		return 'extendedsearch-search-wikipage';
	}
}