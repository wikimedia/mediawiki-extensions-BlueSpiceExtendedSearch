<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$title = $this->out->getTitle();
		if ( $title->equals( \SpecialPage::getTitleFor( 'BSSearchCenter' ) ) === false ) {
			$this->out->addModules( "ext.blueSpiceExtendedSearch.SearchFieldAutocomplete" );
		}
	}

}
