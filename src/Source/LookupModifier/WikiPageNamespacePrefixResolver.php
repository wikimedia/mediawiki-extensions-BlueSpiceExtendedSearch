<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

/**
 * TODO: Revisit this implementaion, it will mess with wildcarding
 */
class WikiPageNamespacePrefixResolver extends Base {

	/**
	 *
	 * @var string
	 */
	protected $simpleQS = [];

	/**
	 *
	 * @var \Title
	 */
	protected $title = null;

	/**
	 *
	 * @var string
	 */
	protected $titleText = '';

	/**
	 *
	 * @var boolean
	 */
	protected $explicitlyMain = false;

	public function apply() {
		if( !$this->setSimpleQS() ) {
			return;
		}

		$this->setIsExlicitQueryOfNS_MAIN();
		$this->setTitle();

		if( $this->doesNotApply() ) {
			return;
		}
		$this->resetNamespaceFilter();
		$this->setNewNamespaceFilterAndQuery();
	}

	protected function setSimpleQS() {
		$aQueryString = $this->oLookup->getQueryString();
		if( !isset( $aQueryString['query'] ) ) {
			return null;
		}
		$this->simpleQS = $aQueryString;
		return true;
	}

	protected function setIsExlicitQueryOfNS_MAIN() {
		$this->titleText = $this->simpleQS['query'];
		if( strpos( $this->titleText, ':' ) === 0 ) {
			$this->titleText = substr( $this->titleText, 1 );
			$this->explicitlyMain = true;
		}
	}

	protected function setTitle() {
		$this->title = \Title::newFromText( $this->titleText );
	}

	protected function doesNotApply() {
		if( $this->title instanceof \Title === false ) {
			return true;
		}

		if( $this->title->getNamespace() === NS_MAIN && !$this->explicitlyMain ) {
			return true;
		}
	}

	protected function resetNamespaceFilter() {
		//We reset all namespace filters
		$this->oLookup->clearFilter( 'namespace_text' );
	}

	public function setNewNamespaceFilterAndQuery() {
		$this->simpleQS['query'] = $this->title->getText();
		$this->oLookup->setQueryString( $this->simpleQS );
		//We use namespace name, because "namespace_name" is available filter on front-end
		$nsText = \BsNamespaceHelper::getNamespaceName( $this->title->getNamespace() );
		$this->oLookup->addTermsFilter( 'namespace_text', $nsText );
	}

	public function undo() {
	}
}