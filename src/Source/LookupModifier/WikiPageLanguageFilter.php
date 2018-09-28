<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

/**
 * Page language filter is special because not all indexed docs have
 * a page_language fields, since its not applicable to all.
 * If PL filter is present, we need to show all pages that have that language set
 * OR the page_language field does not exist entirely.
 *
 */
class WikiPageLanguageFilter extends Base {

	protected $originalMust = [];
	protected $filterValue;

	public function apply() {
		$filters = $this->oLookup->getFilters();
		$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$autoSetLangFilter = $config->get( 'ESAutoSetLangFilter' );
		if( !isset( $filters['terms']['page_language'] ) && !$autoSetLangFilter ) {
			return;
		}
		$filterValue = $this->oLookup->getFilters()['terms']['page_language'];
		if( count( $filterValue ) !== 1 ) {
			if( $autoSetLangFilter ) {
				$autoLangCode = $this->getAutoLangCode();
				$this->oLookup->removeTermsFilter( 'page_language', $filterValue );
				$this->oLookup->addTermsFilter( 'page_language', $autoLangCode );
				$this->filterValue = $autoLangCode;
			} else {
				// ATM multiple selected languages are not supported
				return;
			}
		} else {
			$this->filterValue = $filterValue[0];
		}

		if( !$this->filterValue ) {
			// Just to be sure
			return;
		}

		$this->originalMust = $this->oLookup['query']['bool']['must'];
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
		$this->oLookup['query']['bool']['must'] = $must;
		$this->oLookup->removeTermsFilter( 'page_language', $this->filterValue );
	}

	public function undo() {
		if( !empty( $this->originalMust ) ) {
			$this->oLookup['query']['bool']['must'] = $this->originalMust;
			$this->oLookup->addTermsFilter( 'page_language', $this->filterValue );
		}
	}

	protected function getAutoLangCode() {
		$user = $this->oContext->getUser();
		if( $user->isAnon() ) {
			return $this->oContext->getLanguage()->getCode();
		}
		return $user->getOption( 'language' );
	}
}