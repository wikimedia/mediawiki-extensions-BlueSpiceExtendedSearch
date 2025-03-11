<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\Title\Title;

/**
 * TODO: Revisit this implementaion, it will mess with wildcarding
 */
class WikiPageNamespacePrefixResolver extends LookupModifier {

	/**
	 *
	 * @var string
	 */
	protected $simpleQS = [];

	/**
	 *
	 * @var Title
	 */
	protected $title = null;

	/**
	 *
	 * @var string
	 */
	protected $titleText = '';

	/**
	 *
	 * @var bool
	 */
	protected $explicitlyMain = false;

	public function apply() {
		if ( !$this->setSimpleQS() ) {
			return;
		}

		$this->setIsExlicitQueryOfNS_MAIN();
		$this->setTitle();

		if ( $this->doesNotApply() ) {
			return;
		}
		$this->resetNamespaceFilter();
		$this->setNewNamespaceFilterAndQuery();
	}

	/**
	 *
	 * @return int
	 */
	public function getPriority() {
		return 10;
	}

	protected function setSimpleQS() {
		$aQueryString = $this->lookup->getQueryString();
		if ( !isset( $aQueryString['query'] ) ) {
			return null;
		}
		$this->simpleQS = $aQueryString;
		return true;
	}

	protected function setIsExlicitQueryOfNS_MAIN() { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName, Generic.Files.LineLength.TooLong
		$this->titleText = $this->simpleQS['query'];
		if ( str_starts_with( $this->titleText, ':' ) ) {
			$this->titleText = substr( $this->titleText, 1 );
			$this->explicitlyMain = true;
		}
	}

	protected function setTitle() {
		$titleName = trim( $this->titleText );
		$this->title = Title::newFromText( $titleName );
		if ( str_ends_with( $titleName, ':' ) ) {
			// If search term is ending in a ":", presume
			// user wants to see all results from given NS
			// - set query text to "*"
			$title = Title::newFromText( "$titleName*" );
			if ( $title->getNamespace() !== NS_MAIN ) {
				$this->title = $title;
			}
		}
	}

	protected function doesNotApply() {
		if ( $this->title instanceof Title === false ) {
			return true;
		}

		if ( $this->title->getNamespace() === NS_MAIN && !$this->explicitlyMain ) {
			return true;
		}
	}

	protected function resetNamespaceFilter() {
		// We reset all namespace filters
		$this->lookup->clearFilter( 'namespace' );
	}

	public function setNewNamespaceFilterAndQuery() {
		$this->simpleQS['query'] = $this->title->getText();
		$this->lookup->setQueryString( $this->simpleQS );
		$this->lookup->addTermsFilter( 'namespace', $this->title->getNamespace() );
	}

	public function undo() {
	}
}
