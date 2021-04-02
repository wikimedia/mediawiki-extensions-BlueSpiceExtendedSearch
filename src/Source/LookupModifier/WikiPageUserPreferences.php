<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;

class WikiPageUserPreferences extends Base {
	/** @var int[] */
	protected $namespacesToBoost;

	public function apply() {
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		$permManager = $services->getPermissionManager();
		$user = $this->oContext->getUser();
		$options = $userOptionsLookup->getOptions( $user );

		$namespacesToBoost = [];
		foreach ( $options as $optionName => $optionValue ) {
			if ( strpos( $optionName, 'searchNs' ) !== 0 ) {
				continue;
			}

			$optionValue = (int)$optionValue;
			if ( $optionValue != 1 ) {
				continue;
			}

			$nsId = (int)substr( $optionName, strlen( 'searchNs' ) );
			$oTitle = \Title::makeTitle( $nsId, 'Dummy' );
			if ( $permManager->userCan( 'read', $user, $oTitle ) ) {
				$namespacesToBoost[] = $nsId;
			}
		}

		$this->namespacesToBoost = $namespacesToBoost;
		if ( !empty( $this->namespacesToBoost ) ) {
			$this->oLookup->addShouldTerms( 'namespace', $this->namespacesToBoost, 8, false );
		}
	}

	public function undo() {
		$this->oLookup->removeShouldTerms( 'namespace', $this->namespacesToBoost );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [
			Backend::QUERY_TYPE_AUTOCOMPLETE,
			Backend::QUERY_TYPE_SEARCH
		];
	}
}
