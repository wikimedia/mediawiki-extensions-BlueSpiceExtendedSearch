<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

use BS\ExtendedSearch\Backend as SearchBackend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Source\Base;
use FormatJson;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use Title;

class SearchCenter extends SpecialPage {

	public function __construct() {
		// SearchCenter should only be reached via searchBar
		parent::__construct( 'BSSearchCenter', '', false );
	}

	/**
	 *
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		$this->setHeaders();

		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$pm = MediaWikiServices::getInstance()->getPermissionManager();

		$returnTo = $this->getRequest()->getText( 'returnto' );
		$title = Title::newFromText( $returnTo );
		if ( $title instanceof Title && $title->exists() ) {
			$this->getOutput()->addReturnTo( $title );
		}

		// Query string param that can contain search term or entire lookup object
		$query = $this->getRequest()->getText( 'q' );
		$lookup = $this->lookupFromQuery( $query );

		$queryString = $lookup->getQueryString();
		$rawTerm = $this->getRequest()->getText( 'raw_term' );
		// If user has submitted the form too fast, before
		// Lookup object had time to init/update on client side,
		// we must use raw_term to set the lookup
		if ( $rawTerm !== '' ) {
			// If raw term is contained in the query string, it means lookup did have time to update
			$rawTermIsPartial = $this->isRawTermPartial( $queryString['query'], $rawTerm );

			if ( $queryString['query'] === '' || ( $queryString['query'] !== $rawTerm && !$rawTermIsPartial ) ) {
				$queryString['query'] = $rawTerm;
				$lookup->setQueryString( $queryString );
			}
		}

		$out = $this->getOutput();
		$out->addModules( "ext.blueSpiceExtendedSearch.SearchCenter" );

		$localBackend = SearchBackend::instance();
		$defaultResultStructure = $localBackend->getDefaultResultStructure();

		$base = new Base( $localBackend, [] );
		$sortableFields = $base->getMappingProvider()->getSortableFields();
		// Add _score manually, as its not a real field
		array_unshift( $sortableFields, '_score' );

		$availableTypes = [];
		$resultStructures = [];

		foreach ( $localBackend->getSources() as $sourceKey => $source ) {
			$resultStructure = $source->getFormatter()->getResultStructure( $defaultResultStructure );
			$resultStructures[$source->getTypeKey()] = $resultStructure;

			$searchPermission = $source->getSearchPermission();
			if ( !$searchPermission || $pm->userHasRight( $this->getUser(), $searchPermission ) ) {
				$availableTypes[] = $source->getTypeKey();
			}
		}

		$out->enableOOUI();
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-tools' ] ) );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-alt-search' ] ) );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-results' ] ) );

		if ( $lookup ) {
			$out->addJsConfigVars( 'bsgLookupConfig', FormatJson::encode( $lookup ) );
		}

		// Structure of the result displayed in UI, decorated by each source
		$out->addJsConfigVars( 'bsgESResultStructures', $resultStructures );
		// Array of fields available for sorting
		$out->addJsConfigVars( 'bsgESSortableFields', $sortableFields );
		// Array of each source's types.
		$out->addJsConfigVars( 'bsgESAvailbleTypes', $availableTypes );
		$out->addJsConfigVars( 'bsgESResultsPerPage', 25 );
		$out->addJsConfigVars(
			'ESSearchCenterDefaultFilters', $config->get( 'ESSearchCenterDefaultFilters' )
		);
		$out->addJsConfigVars( 'bsgESUserCanExport', $this->userCanExport() );
		$out->addJsConfigVars(
			'bsgESOfferOperatorSuggestion', $config->get( 'ESOfferOperatorSuggestion' )
		);
		$out->addJsConfigVars( 'bsgESEnableTypeFilter', $config->get( 'ESEnableTypeFilter' ) );
	}

	/**
	 * Makes lookup from given string, if possible,
	 * otherwise returns empty Lookup
	 *
	 * @param string $query
	 * @return Lookup
	 */
	protected function lookupFromQuery( $query ) {
		$lookup = new Lookup();
		if ( !$query ) {
			return $lookup;
		}

		$parseStatus = FormatJson::parse( $query, FormatJson::FORCE_ASSOC );
		if ( $parseStatus->isOK() ) {
			return new Lookup( $parseStatus->getValue() );
		}

		if ( is_string( $query ) == false ) {
			return $lookup;
		}

		$lookup->setQueryString( $query );
		return $lookup;
	}

	/**
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'bluespice';
	}

	/**
	 *
	 * @return bool
	 */
	private function userCanExport() {
		$pageToTest = Title::makeTitle( NS_MEDIAWIKI, 'Dummy' );
		if ( MediaWikiServices::getInstance()
			->getPermissionManager()
			->userCan( 'edit', $this->getUser(), $pageToTest )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Determine if raw term is a part of the query string
	 * This is true if search term is namespaced or contains a subpage syntax
	 *
	 * @param string $query
	 * @param string $raw
	 * @return bool
	 */
	private function isRawTermPartial( $query, $raw ) {
		return strpos( strtolower( $query ), strtolower( $raw ) ) !== false;
	}
}
