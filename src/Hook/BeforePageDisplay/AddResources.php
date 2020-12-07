<?php

namespace BS\ExtendedSearch\Hook\BeforePageDisplay;

class AddResources extends \BlueSpice\Hook\BeforePageDisplay {

	protected function doProcess() {
		$title = $this->out->getTitle();
		if ( $title->equals( \SpecialPage::getTitleFor( 'BSSearchCenter' ) ) === false ) {
			$this->out->addJsConfigVars(
				'ESUseSubpagePillsAutocomplete',
				$this->useSubpagePills()
			);

			$this->out->addModules( "ext.blueSpiceExtendedSearch.SearchFieldAutocomplete" );
		}
	}

	private function useSubpagePills() {
		if ( !$this->getConfig()->get( 'ESAutoRecognizeSubpages' ) ) {
			return false;
		}
		if (
			$this->getConfig()->has( 'ESUseSubpagePillsAutocomplete' ) &&
			!$this->getConfig()->get( 'ESUseSubpagePillsAutocomplete' )
		) {
			return false;
		}

		return true;
	}
}
