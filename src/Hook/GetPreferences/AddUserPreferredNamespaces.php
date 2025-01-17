<?php

namespace BS\ExtendedSearch\Hook\GetPreferences;

use BlueSpice\Hook\GetPreferences;
use MediaWiki\Title\Title;

class AddUserPreferredNamespaces extends GetPreferences {

	protected function doProcess() {
		$namespaces = $this->getContext()->getLanguage()->getNamespaces();
		$user = $this->getContext()->getUser();
		$pm = \MediaWiki\MediaWikiServices::getInstance()->getPermissionManager();

		$namespaceValues = [];
		foreach ( $namespaces as $namespaceId => $namespace ) {
			$testTitle = Title::makeTitle( $namespaceId, 'ESDummy' );

			if ( $namespaceId >= 0 && $pm->userCan( 'read', $user, $testTitle ) ) {
				$label = $testTitle->getNsText();

				if ( $namespaceId === NS_MAIN ) {
					$label = wfMessage( 'bs-ns_main' )->plain();
				}

				$namespaceValues[$label] = $namespaceId;
			}
		}

		$this->preferences['searchNs'] = [
			'type' => 'multiselect',
			'label' => wfMessage( 'bs-extendedsearch-user-preferred-namespaces' )->plain(),
			'section' => 'extendedsearch',
			'options' => $namespaceValues
		];

		return true;
	}

}
