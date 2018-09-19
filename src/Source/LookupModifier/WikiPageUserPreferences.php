<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageUserPreferences extends Base {
	protected $namespacesToBoost;

	public function apply() {
		$options = $this->oContext->getUser()->getOptions();

		$namespacesToBoost = [];
		foreach( $options as $optionName => $optionValue ) {
			if( strpos( $optionName, 'searchNs' ) !== 0 ) {
				continue;
			}
			if( $optionValue === false ) {
				continue;
			}

			$nsId = (int)substr( $optionName, strlen( 'searchNs' ) );
			$oTitle = \Title::makeTitle( $nsId, 'Dummy' );
			if( $oTitle->userCan( 'read' ) ) {
				$namespacesToBoost[] = $nsId;
			}
		}

		$this->namespacesToBoost = $namespacesToBoost;
		if( !empty( $this->namespacesToBoost ) ) {
			$this->oLookup->addShouldTerms( 'namespace', $this->namespacesToBoost, 8, true );
		}
	}

	public function undo() {
		$this->oLookup->removeShouldTerms( 'namespace', $this->namespacesToBoost );
	}
}