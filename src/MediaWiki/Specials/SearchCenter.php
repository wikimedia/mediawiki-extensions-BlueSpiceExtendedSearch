<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

class SearchCenter extends \SpecialPage {

	public function __construct( $name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false ) {
		//SearchCenter should only be reached via searchBar
		parent::__construct( 'BSSearchCenter', $restriction, false );
	}

	public function execute( $subPage ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->addModules( "ext.blueSpiceExtendedSearch.SearchCenter" );

		$localBackend = \BS\ExtendedSearch\Backend::instance( 'local' );
		$resultStructure = $localBackend->getResultStructure();

		//Add _score manually, as its not a real field
		$sortableFields = ['_score'];
		$allowedSortableFieldTypes = ['date', 'time', 'integer'];

		$availableTypes = [];

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
					if( in_array( 'fielddata', $fieldConfig ) &&  $fieldConfig['fielddata'] == true ) {
						$sortableFields[] = $fieldName;
					}
				}
			}

			$source->getFormatter()->modifyResultStructure( $resultStructure );

			$availableTypes[] = $source->getTypeKey();

			$sourceConfig[$sourceKey] = new \stdClass(); //In some future
			//there might be additional configs per source. ATM we only need
			//the key
		}

		$out->enableOOUI();
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-hitcount' ] ) );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-tools' ] ) );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-results' ] ) );

		$out->addJsConfigVars( 'bsgESSources', $sourceConfig );
		//Structure of the result displayed in UI, decorated by each source
		$out->addJsConfigVars( 'bsgESResultStructure', $resultStructure );
		//Array of fields available for sorting
		$out->addJsConfigVars( 'bsgESSortableFields', $sortableFields );
		//Array of each source's types.
		$out->addJsConfigVars( 'bsgESAvailbleTypes', $availableTypes );
		//TODO: Get from settings once that structure is created
		$out->addJsConfigVars( 'bsgESResultsPerPage', 25 );
	}

	protected function getGroupName() {
		return 'bluespice';
	}
}