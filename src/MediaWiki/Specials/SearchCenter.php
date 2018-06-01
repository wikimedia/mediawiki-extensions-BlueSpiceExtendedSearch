<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

class SearchCenter extends \SpecialPage {

	public function __construct( $name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false ) {
		//SearchCenter should only be reached via searchBar
		parent::__construct( 'BSSearchCenter', $restriction, false );
	}

	public function execute( $subPage ) {
		$this->setHeaders();

		//Query string param that can contain search term or entire lookup object
		$query = $this->getRequest()->getText( 'q' );
		$lookup = $this->lookupFromQuery( $query );

		$out = $this->getOutput();
		$out->addModules( "ext.blueSpiceExtendedSearch.SearchCenter" );

		$localBackend = \BS\ExtendedSearch\Backend::instance( 'local' );
		$defaultResultStructure = $localBackend->getDefaultResultStructure();

		//Add _score manually, as its not a real field
		$sortableFields = ['_score'];
		$allowedSortableFieldTypes = ['date', 'time', 'integer'];

		$availableTypes = [];
		$resultStructures = [];

		$sourceConfig = [];
		foreach( $localBackend->getSources() as $sourceKey => $source ) {
			foreach( $source->getMappingProvider()->getPropertyConfig() as $fieldName => $fieldConfig ) {
				if( in_array( $fieldName, $sortableFields ) ) {
					continue;
				}

				if( in_array( $fieldConfig['type'], $allowedSortableFieldTypes ) ) {
					$sortableFields[] = $fieldName;
					continue;
				}

				if( $fieldConfig['type'] == 'text' ) {
					if( isset( $fieldConfig['fielddate'] ) &&  $fieldConfig['fielddata'] == true ) {
						$sortableFields[] = $fieldName;
					}
				}
			}

			$resultStructure = $source->getFormatter()->getResultStructure( $defaultResultStructure );
			$resultStructures[$source->getTypeKey()] = $resultStructure;

			$availableTypes[] = $source->getTypeKey();

			$sourceConfig[$sourceKey] = new \stdClass(); //In some future
			//there might be additional configs per source. ATM we only need
			//the key
		}

		$out->enableOOUI();
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-hitcount' ] ) );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-tools' ] ) );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-results' ] ) );

		if( $lookup ) {
			//How else can we pass info to client? I dont like adding HTML node either
			$out->addJsConfigVars( 'bsgLookupConfig', \FormatJson::encode( $lookup ) );
		}

		$out->addJsConfigVars( 'bsgESSources', $sourceConfig );
		//Structure of the result displayed in UI, decorated by each source
		$out->addJsConfigVars( 'bsgESResultStructures', $resultStructures );
		//Array of fields available for sorting
		$out->addJsConfigVars( 'bsgESSortableFields', $sortableFields );
		//Array of each source's types.
		$out->addJsConfigVars( 'bsgESAvailbleTypes', $availableTypes );
		//TODO: Get from settings once that structure is created
		$out->addJsConfigVars( 'bsgESResultsPerPage', 25 );
	}

	/**
	 * Makes lookup from given string, if possible,
	 * otherwise returns empty Lookup
	 *
	 * @param string $query
	 * @return \BS\ExtendedSearch\Lookup
	 */
	protected function lookupFromQuery( $query ) {
		$lookup = new \BS\ExtendedSearch\Lookup();
		if( !$query ) {
			return $lookup;
		}

		$parseStatus = \FormatJson::parse( $query, \FormatJson::FORCE_ASSOC );
		if( $parseStatus->isOK() ) {
			return new \BS\ExtendedSearch\Lookup( $parseStatus->getValue() );
		}

		if( is_string( $query ) == false ) {
			return $lookup;
		}

		$lookup->setQueryString( $query );
		return $lookup;
	}

	protected function getGroupName() {
		return 'bluespice';
	}
}