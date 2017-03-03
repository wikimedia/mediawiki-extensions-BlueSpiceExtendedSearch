<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageNamespacePrefixResolver extends Base {

	/**
	 *
	 * @var string
	 */
	protected $simpleQS = '';

	/**
	 *
	 * @var \Title
	 */
	protected $title = null;

	public function apply() {
		$this->setSimpleQS();
		$this->setTitle();
		if( $this->doesNotApply() ) {
			return;
		}
		$this->resetNamespaceFilter();
		$this->setNewNamespaceFilterAndQuery();
	}

	protected function setSimpleQS() {
		$aQueryString = $this->oLookup->getSimpleQueryString();
		$this->simpleQS = $aQueryString['query'];
	}

	protected function setTitle() {
		$this->title = \Title::newFromText( $this->simpleQS );
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
		$sStartsWithColor = strpos( $this->simpleQS, ':') === 0;
		$titleInMAIN = $this->title->getNamespace() === NS_MAIN;

		return $titleInMAIN && !$sStartsWithColor;
	}

	protected function resetNamespaceFilter() {
		//We reset all namespace filters
		$this->oLookup->removeFilter( 'namespace', range( -2, 65536 ) ); //Ugly --> implement "setFilter"?
	}

	public function setNewNamespaceFilterAndQuery() {
		$this->oLookup->setSimpleQueryString( $this->title->getText() );
		$this->oLookup->addFilter( 'namespace', $this->title->getNamespace() );
	}
}