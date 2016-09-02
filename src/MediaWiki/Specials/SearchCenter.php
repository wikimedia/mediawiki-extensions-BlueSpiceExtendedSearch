<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

class SearchCenter extends \SpecialPage {
	public function __construct($name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false) {
		parent::__construct( 'BSSearchCenter' );
	}

	public function execute( $subPage ) {
		$oSearchField = new \OOUI\TextInputWidget();

		$this->getOutput()->enableOOUI();
		$this->getOutput()->addHTML( $oSearchField );
	}
}