<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\LookupModifier\BaseExtensionAggregation;
use BS\ExtendedSearch\Source\LookupModifier\BaseTagsAggregation;
use BS\ExtendedSearch\Source\LookupModifier\BaseScoreSortWhenShould;
use BS\ExtendedSearch\Source\LookupModifier\BaseAutocompleteSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\BaseSimpleQSFields;
use BS\ExtendedSearch\Source\LookupModifier\Base as LookupModifier;

class Base {

	protected $lookupModifiers = [
		LookupModifier::TYPE_SEARCH => [
			'base-extensionaggregation' => BaseExtensionAggregation::class,
			'base-tagsaggregation' => BaseTagsAggregation::class,
			'base-simpleqsfields' => BaseSimpleQSFields::class
		],
		LookupModifier::TYPE_AUTOCOMPLETE => [
			'base-acsourcefields' => BaseAutocompleteSourceFields::class
		]
	];

	/**
	 *
	 * @var \BS\ExtendedSearch\Backend
	 */
	protected $oBackend = null;

	/**
	 *
	 * @var \Config
	 */
	protected $oConfig = null;

	/**
	 *
	 * @param \BS\ExtendedSearch\Backend
	 * @param array $aConfig
	 */
	public function __construct( $oBackend, $aConfig ) {
		$this->oBackend = $oBackend;
		$this->oConfig = new \HashConfig( $aConfig );
	}

	/**
	 *
	 * @return \Config
	 */
	public function getConfig() {
		return $this->oConfig;
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Backend
	 */
	public function getBackend() {
		return $this->oBackend;
	}

	/**
	 *
	 * @return string
	 */
	public function getTypeKey() {
		return $this->getConfig()->get( 'sourcekey' );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\MappingProvider\Base
	 */
	public function getMappingProvider() {
		return new MappingProvider\Base();
	}

	/**
	 * @return BS\ExtendedSearch\Crawler\Base
	 */
	public function getCrawler() {
		return new Crawler\Base( $this->oConfig );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\DocumentProvider\Base
	 */
	public function getDocumentProvider() {
		return new DocumentProvider\Base();
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Updater\Base
	 */
	public function getUpdater() {
		return new Updater\Base( $this );
	}

	/**
	 *
	 * @param \BS\ExtendedSearch\Lookup
	 * @param \IContextSource $oContext
	 * @param string
	 * @return BS\ExtendedSearch\Source\LookupModifier\Base[]
	 */
	public function getLookupModifiers( $oLookup, $oContext, $sType = LookupModifier::TYPE_SEARCH ) {
		if( !isset( $this->lookupModifiers[$sType] ) ) {
			return [];
		}

		$lookupModifiers = [];
		foreach( $this->lookupModifiers[$sType] as $key => $class ) {
			$lookupModifiers[$key] = new $class( $oLookup, $oContext );
		}

		return $lookupModifiers;
	}

	/**
	 *
	 * @return array
	 */
	public function getIndexSettings() {
		//This kind of tokenizing breaks words in 3-char parts,
		//which makes it possible to match single words in compound words
		return [
			"settings" => [
				"number_of_shards" => 1, //Only for testing purposes on small sample, remove or increase for production
				"analysis" => [
					"filter" => [
						"autocomplete_filter" => [
							"type" => "ngram",
							"min_gram" => 3,
							"max_gram" => 15
						]
					],
					"analyzer" => [
						"autocomplete" => [
							"type" => "custom",
							"tokenizer" => "standard", //Change
							"filter" => [
								"lowercase",
								"autocomplete_filter"
							]
						]
					]
				]
			]
		];
	}

	/**
	 *
	 * @param array $aDocumentConfigs
	 * @return \Elastica\Bulk\ResponseSet
	 */
	public function addDocumentsToIndex( $aDocumentConfigs ) {
		$oElasticaIndex = $this->getBackend()->getIndexByType( $this->getTypeKey() );
		$oType = $oElasticaIndex->getType( $this->getTypeKey() );
		$aDocs = [];
		foreach( $aDocumentConfigs as $aDC ) {
			$aDocs[] = new \Elastica\Document( $aDC['id'], $aDC );
		}

		$oResult = $oType->addDocuments( $aDocs );
		if( !$oResult->isOk() ) {
			wfDebugLog(
				'BSExtendedSearch',
				"Adding documents failed: {$oResult->getError()}"
			);
		}
		$oElasticaIndex->refresh();

		return $oResult;
	}

	/**
	 *
	 * @param array $aDocumentIds
	 * @return \Elastica\Bulk\ResponseSet
	 */
	public function deleteDocumentsFromIndex( $aDocumentIds ) {
		$oElasticaIndex = $this->getBackend()->getIndex();
		$aDocs = [];
		foreach ( $aDocumentIds as $sDocumentId ) {
			$aDocs[] = new \Elastica\Document( $sDocumentId );
		}

		$oResult = $oElasticaIndex->deleteDocuments( $aDocs );
		if( !$oResult->isOk() ) {
			wfDebugLog(
				'BSExtendedSearch',
				"Adding documents failed: {$oResult->getError()}"
			);
		}

		$oElasticaIndex->refresh();

		return $oResult;
	}

	public function getFormatter() {
		return new Formatter\Base( $this );
	}
}