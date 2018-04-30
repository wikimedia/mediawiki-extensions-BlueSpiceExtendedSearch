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

	public function apply() {
		if( !$this->setSimpleQS() ) {
			return;
		}
		$this->setTitle();
		if( $this->doesNotApply() ) {
			return;
		}
		$this->resetNamespaceFilter();
		$this->setNewNamespaceFilterAndQuery();
	}

	protected function setSimpleQS() {
		$aQueryString = $this->oLookup->getSimpleQueryString();
		if( !$aQueryString ) {
			return null;
		}
		$this->simpleQS = $aQueryString;
	}

	protected function setTitle() {
		$this->title = \Title::newFromText( $this->simpleQS['query'] );
	}

	protected function doesNotApply() {
		if( $this->title instanceof \Title === false ) {
			return true;
		}
		if( $this->notAnExplicitQueryOfNS_MAIN() ) {
			return true;
		}
	}

	protected function notAnExplicitQueryOfNS_MAIN() {
		$sStartsWithColon = strpos( $this->simpleQS['query'], ':') === 0;
		$titleInMAIN = $this->title->getNamespace() === NS_MAIN;

		return $titleInMAIN && !$sStartsWithColon;
	}

	protected function resetNamespaceFilter() {
		//We reset all namespace filters
		$this->oLookup->clearFilter( 'namespace_text' );
	}

	public function setNewNamespaceFilterAndQuery() {
		$this->simpleQS['query'] = $this->title->getText();
		$this->oLookup->setSimpleQueryString( $this->simpleQS );
		//We use namespace name, because "namespace_name" is available filter on front-end
		$nsText = \BsNamespaceHelper::getNamespaceName( $this->title->getNamespace() );
		$this->oLookup->addTermsFilter( 'namespace_text', $nsText );
	}

	public function undo() {
	}
}