<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

class SearchCenter extends \SpecialPage {
	public function __construct( $name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false ) {
		parent::__construct( 'BSSearchCenter' );
	}

	public function execute( $subPage ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->addModules( "ext.blueSpiceExtendedSearch.SearchCenter" );

		$oSearchField = new \OOUI\TextInputWidget([
			'placeholder' => wfMessage( 'bs-extendedsearch-search-input-placeholder' )->plain(),
			'id' => 'bs-es-tf-search',
			'infusable' => true
		]);
		$oButton = new \OOUI\ButtonWidget([
			'id' => 'bs-es-btn-search',
			'icon' => 'search',
			'infusable' => true
		]);
		$oFieldLayout = new \OOUI\ActionFieldLayout( $oSearchField, $oButton );

		$out->enableOOUI();
		$out->addHTML( $oFieldLayout );
	}

	protected function getGroupName() {
		return 'bluespice';
	}
}