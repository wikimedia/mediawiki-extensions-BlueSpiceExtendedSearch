<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\MediaWikiServices;

/**
 * Page language filter is special because not all indexed docs have
 * a page_language fields, since its not applicable to all.
 * If PL filter is present, we need to show all pages that have that language set
 * OR the page_language field does not exist entirely.
 *
 */
class WikiPageLanguageFilter extends LookupModifier {

	/** @var array */
	protected $originalMust = [];
	/** @var string */
	protected $filterValue;

	public function apply() {
		$this->filterValue = null;
		$filters = $this->lookup->getFilters();
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$autoSetLangFilter = $config->get( 'ESAutoSetLangFilter' );
		$filterValue = null;
		if ( isset( $filters['terms']['page_language'] ) ) {
			$filterValue = $filters['terms']['page_language'];
		}

		if ( $filterValue && count( $filterValue ) !== 1 ) {
			if ( $autoSetLangFilter ) {
				$autoLangCode = $this->getAutoLangCode();
				$this->lookup->removeTermsFilter( 'page_language', $filterValue );
				$this->lookup->addTermsFilter( 'page_language', $autoLangCode );
				$this->filterValue = $autoLangCode;
			} else {
				// ATM multiple selected languages are not supported
				return;
			}
		} else {
			$this->filterValue = $filterValue[0] ?? null;
		}
		if ( !$this->filterValue ) {
			// Just to be sure
			return;
		}

		$this->originalMust = $this->lookup['query']['bool']['must'];
		$must = $this->originalMust;
		$languageFilter = [
			"bool" => [
				"minimum_should_match" => 1,
				"should" => [
					[
						"bool" => [
							"must" => [
								"match" => [
									"page_language" => [
										"query" => $this->filterValue
									]
								]
							]
						]
					],
					[
						"bool" => [
							"must_not" => [
								"exists" => [
									"field" => "page_language"
								]
							]
						]
					]
				]
			]
		];
		$must[] = $languageFilter;
		$this->lookup['query']['bool']['must'] = $must;
		$this->lookup->removeTermsFilter( 'page_language', $this->filterValue );
	}

	public function undo() {
		if ( !empty( $this->originalMust ) ) {
			$this->lookup['query']['bool']['must'] = $this->originalMust;
			$this->lookup->addTermsFilter( 'page_language', $this->filterValue );
		}
	}

	/**
	 *
	 * @return string
	 */
	protected function getAutoLangCode() {
		$user = $this->context->getUser();
		if ( $user->isAnon() ) {
			return $this->context->getLanguage()->getCode();
		}
		return MediaWikiServices::getInstance()->getUserOptionsLookup()
			->getOption( $user, 'language' );
	}
}
