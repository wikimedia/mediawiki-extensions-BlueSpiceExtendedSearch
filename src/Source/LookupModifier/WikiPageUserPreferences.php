<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageUserPreferences extends Base {

	public function apply() {
		$aOptions = $this->oContext->getUser()->getOptions();

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
				//Boost results in "default" namespaces
				$this->oLookup->addShouldMatch( 'namespace', $iNSid, 4 );
			}
		}
	}

	public function undo() {
	}
}