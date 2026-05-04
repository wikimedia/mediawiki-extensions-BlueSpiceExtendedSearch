<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;

class WikiPageUserPreferences extends LookupModifier {
	/** @var int[] */
	protected $namespacesToBoost;

	/** @var UtilityFactory */
	protected $utilityFactory;

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @param UtilityFactory|null $utilityFactory
	 */
	public function __construct( $lookup, $context, ?UtilityFactory $utilityFactory ) {
		parent::__construct( $lookup, $context );
		$this->utilityFactory =
			$utilityFactory ?? MediaWikiServices::getInstance()->getService( 'MWStakeCommonUtilsFactory' );
	}

	public function apply() {
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		$permManager = $services->getPermissionManager();
		$user = $this->context->getUser();
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
			$namespacesToBoost[] = $nsId;
		}

		$readableNamespaces = $this->utilityFactory->getReadableNamespacesHelper();
		$this->namespacesToBoost = array_values(
			array_diff( $namespacesToBoost, $readableNamespaces->getRestrictedNamespaces( $user ) )
		);
		if ( !empty( $this->namespacesToBoost ) ) {
			$this->lookup->addShouldTerms( 'namespace', $this->namespacesToBoost, 8, false );
		}
	}

	public function undo() {
		$this->lookup->removeShouldTerms( 'namespace', $this->namespacesToBoost );
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
