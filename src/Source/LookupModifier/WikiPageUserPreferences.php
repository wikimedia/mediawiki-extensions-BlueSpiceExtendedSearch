<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageUserPreferences extends Base {

	public function apply() {
		$aOptions = $this->oContext->getUser()->getOptions();
		$aNamespacesToBeSearchedDefault = [];
		foreach( $aOptions as $sOptionName => $mOptionValue ) {
			if( strpos( $sOptionName, 'searchNs' ) !== 0 ) {
				continue;
			}
			if( $mOptionValue === false ) {
				continue;
			}

			$iNSid = (int)substr( $sOptionName, strlen( 'searchNs' ) );
			$oTitle = \Title::makeTitle( $iNSid, 'Dummy' );
			if( $oTitle->userCan( 'read' ) ) {
				$aNamespacesToBeSearchedDefault[] = $iNSid;
			}
		}

		$this->oLookup->addFilter( 'namespace', $aNamespacesToBeSearchedDefault );
	}
}