<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\LookupModifier\BaseExtensionAggregation;
use BS\ExtendedSearch\Source\LookupModifier\BaseTagsAggregation;
use BS\ExtendedSearch\Source\LookupModifier\BaseAutocompleteSourceFields;
use BS\ExtendedSearch\Source\LookupModifier\BaseSimpleQSFields;
use BS\ExtendedSearch\Source\LookupModifier\BaseWildcarder;
use BS\ExtendedSearch\Source\LookupModifier\BaseSortByID;
use BS\ExtendedSearch\Source\LookupModifier\BaseTitleSecurityTrimmings;
use BS\ExtendedSearch\Source\LookupModifier\BaseUserRelevance;
use BS\ExtendedSearch\Source\LookupModifier\BaseTypeSecurityTrimming;
use BS\ExtendedSearch\Source\LookupModifier\Base as LookupModifier;

class Base {

	protected $lookupModifiers = [
		LookupModifier::TYPE_SEARCH => [
			'base-extensionaggregation' => BaseExtensionAggregation::class,
			'base-tagsaggregation' => BaseTagsAggregation::class,
			'base-simpleqsfields' => BaseSimpleQSFields::class,
			'base-wildcarder' => BaseWildcarder::class,
			'base-idsort' => BaseSortByID::class,
			'base-userrelevance' => BaseUserRelevance::class,
			'base-typesecuritytrimmings' => BaseTypeSecurityTrimming::class,
			'base-titlesecuritytrimmings' => BaseTitleSecurityTrimmings::class,
		],
		LookupModifier::TYPE_AUTOCOMPLETE => [
			'base-acsourcefields' => BaseAutocompleteSourceFields::class,
			'base-typesecuritytrimmings' => BaseTypeSecurityTrimming::class,
			'base-titlesecuritytrimmings' => BaseTitleSecurityTrimmings::class,
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
	 * @return \BS\ExtendedSearch\Source\Crawler\Base
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
	 * @return \BS\ExtendedSearch\Source\LookupModifier\Base[]
	 */
	public function getLookupModifiers( $oLookup, $oContext, $sType = LookupModifier::TYPE_SEARCH ) {
		if( !isset( $this->lookupModifiers[$sType] ) ) {
			return [];
		}

		$additionalLookupModifiers = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchAdditionalLookupModifiers' );

		$lmClasses = $this->lookupModifiers[$sType];
		if ( isset( $additionalLookupModifiers[$this->getTypeKey()] ) &&
			isset( $additionalLookupModifiers[$this->getTypeKey()][$sType] ) ) {
			$lmClasses = array_merge(
				$lmClasses,
				$additionalLookupModifiers[$this->getTypeKey()][$sType]
			);
		}

		$lookupModifiers = [];
		foreach( $lmClasses as $key => $class ) {
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
				//"number_of_shards" => 1, //Only for testing purposes on small sample, remove or increase for production
				"analysis" => [
					"filter" => [
						"autocomplete_filter" => [
							"type" => "ngram",
							"min_gram" => 1,
							"max_gram" => 23
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

	public function runAdditionalSetupRequests( \Elastica\Client $client ) {
		return;
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
		$oElasticaIndex = $this->getBackend()->getIndexByType( $this->getTypeKey() );
		$aDocs = [];
		foreach ( $aDocumentIds as $sDocumentId ) {
			$aDocs[] = new \Elastica\Document( $sDocumentId );
		}

		// Calling \Elastica\Client::deleteDocuments() does not set the type,
		// causing request to fail
		$bulk = new \Elastica\Bulk( $oElasticaIndex->getClient() );
		$bulk->setIndex( $oElasticaIndex->getName() );
		$bulk->setType( $this->getTypeKey() );
		$bulk->addDocuments( $aDocs, \Elastica\Bulk\Action::OP_TYPE_DELETE );

		$oResult = $bulk->send();

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

	public function getSearchPermission() {
		// Default - no permission required
		return '';
	}
}