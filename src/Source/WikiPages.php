<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\ISearchCrawler;
use BS\ExtendedSearch\ISearchDocumentProvider;
use BS\ExtendedSearch\ISearchMappingProvider;
use BS\ExtendedSearch\ISearchResultFormatter;
use BS\ExtendedSearch\ISearchUpdater;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Plugin\IPostProcessor;
use BS\ExtendedSearch\PostProcessor;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageAutocompleteRemoveUnwanted;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageAutocompleteSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageBoosters;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageCategoriesAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageLanguageAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageLanguageFilter;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageNamespacePrefixResolver;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageNamespaceTextAggregation;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageQSSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageRemoveUnwanted;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageRenderedContentHighlight;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageSecurityTrimming;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageSubpageFilter;
use BS\ExtendedSearch\Source\LookupModifier\WikiPageUserPreferences;
use BS\ExtendedSearch\Source\PostProcessor\WikiPage as WikiPagePostProcessor;
use MediaWiki\Context\IContextSource;

class WikiPages extends GenericSource {

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Crawler\WikiPage
	 */
	public function getCrawler(): ISearchCrawler {
		return $this->makeObjectFromSpec( [
			'class' => Crawler\WikiPage::class,
			'args' => [ $this->config ],
			'services' => [ 'DBLoadBalancer', 'JobQueueGroup' ]
		] );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\WikiPage
	 */
	public function getDocumentProvider(): ISearchDocumentProvider {
		return $this->makeObjectFromSpec( [
			'class' => DocumentProvider\WikiPage::class,
			'services' => [
				'HookContainer', 'ContentRenderer', 'RevisionLookup', 'PageProps', 'Parser',
				'RedirectLookup', 'UserFactory', 'RevisionRenderer'
			]
		] );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\MappingProvider\WikiPage
	 */
	public function getMappingProvider(): ISearchMappingProvider {
		return new MappingProvider\WikiPage();
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Updater\WikiPage
	 */
	public function getUpdater(): ISearchUpdater {
		return new Updater\WikiPage( $this );
	}

	/**
	 *
	 * @return Formatter\WikiPageFormatter
	 */
	public function getFormatter(): ISearchResultFormatter {
		return new Formatter\WikiPageFormatter( $this );
	}

	/**
	 *
	 * @return string
	 */
	public function getSearchPermission(): string {
		return 'extendedsearch-search-wikipage';
	}

	/**
	 * @param PostProcessor $postProcessorRunner
	 *
	 * @return IPostProcessor[]
	 */
	public function getPostProcessors( PostProcessor $postProcessorRunner ): array {
		return [ new WikiPagePostProcessor( $postProcessorRunner ) ];
	}

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 *
	 * @return array
	 */
	public function getLookupModifiers( Lookup $lookup, IContextSource $context ): array {
		$modifiers = parent::getLookupModifiers( $lookup, $context );
		$modifiers[] = new WikiPageNamespaceTextAggregation( $lookup, $context );
		$modifiers[] = new WikiPageUserPreferences( $lookup, $context );
		$modifiers[] = new WikiPageNamespacePrefixResolver( $lookup, $context );
		$modifiers[] = new WikiPageSecurityTrimming( $lookup, $context );
		$modifiers[] = new WikiPageCategoriesAggregation( $lookup, $context );
		$modifiers[] = new WikiPageRenderedContentHighlight( $lookup, $context );
		$modifiers[] = new WikiPageQSSourceFields( $lookup, $context );
		$modifiers[] = new WikiPageBoosters( $lookup, $context );
		$modifiers[] = new WikiPageSubpageFilter( $lookup, $context );
		$modifiers[] = new WikiPageRemoveUnwanted( $lookup, $context );
		$modifiers[] = new WikiPageLanguageAggregation( $lookup, $context );
		$modifiers[] = new WikiPageLanguageFilter( $lookup, $context );
		$modifiers[] = new WikiPageAutocompleteRemoveUnwanted( $lookup, $context );
		$modifiers[] = new WikiPageAutocompleteSourceFields( $lookup, $context );

		return $modifiers;
	}
}
