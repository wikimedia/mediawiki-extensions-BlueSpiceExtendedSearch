<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$title = $this->out->getTitle();
		if( $title != \SpecialPage::getTitleFor( 'BSSearchCenter' ) ) {
			$this->out->addJsConfigVars(
				"ESUseCompactAutocomplete",
				$this->getConfig()->get( 'ESCompactAutocomplete' )
			);
			$this->out->addModules( "ext.blueSpiceExtendedSearch.SearchFieldAutocomplete" );
			$this->out->addModuleStyles(
				"ext.blueSpiceExtendedSearch.Autocomplete.styles"
			);
			$this->out->addModuleStyles(
				"ext.blueSpiceExtendedSearch.SearchBar.styles"
			);
		}

		$autocompleteConfig = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAutocomplete' );
		$sourceIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchSourceIcons' );

		$this->out->addJsConfigVars( 'bsgESAutocompleteConfig', $autocompleteConfig );
		$this->out->addJsConfigVars( 'bsgESSourceIcons', $sourceIcons );
	}

}
