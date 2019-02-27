<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$title = $this->out->getTitle();
		if ( $title->equals( \SpecialPage::getTitleFor( 'BSSearchCenter' ) ) === false ) {
			$this->out->addJsConfigVars(
				"ESUseCompactAutocomplete",
				$this->getConfig()->get( 'ESCompactAutocomplete' )
			);

			$this->out->addModules( "ext.blueSpiceExtendedSearch.SearchFieldAutocomplete" );
		}

		$autocompleteConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );
		$sourceIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSourceIcons' );

		$this->out->addJsConfigVars( 'bsgESAutocompleteConfig', $autocompleteConfig );
		$this->out->addJsConfigVars( 'bsgESSourceIcons', $sourceIcons );
	}

}
