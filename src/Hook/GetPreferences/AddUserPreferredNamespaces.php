<?php

namespace BS\ExtendedSearch\Hook\GetPreferences;

use BlueSpice\Hook\GetPreferences;

class AddUserPreferredNamespaces extends GetPreferences {

	protected function doProcess() {
		$namespaces = $this->getContext()->getLanguage()->getNamespaces();

		$namespaceValues = [];
		foreach( $namespaces as $namespaceId => $namespace ) {
			$testTitle = \Title::makeTitle( $namespaceId, 'ESDummy' );

			if( $namespaceId >= 0 && $testTitle->userCan( 'read' ) ) {
				$label = $testTitle->getNsText();

				if( $namespaceId === NS_MAIN ) {
					$label = wfMessage( 'bs-ns_main' )->plain();
				}

				$namespaceValues[$label] = $namespaceId;
			}
		}

		$this->preferences['searchNs'] = array(
			'type' => 'multiselect',
			'label' => wfMessage( 'bs-extendedsearch-user-preferred-namespaces' )->plain(),
			'section' => 'extendedsearch',
			'options' => $namespaceValues
		);

		return true;
	}

}