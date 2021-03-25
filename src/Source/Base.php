<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ILookupModifier;
use BS\ExtendedSearch\IPostProcessor;
use BS\ExtendedSearch\PostProcessor;
use BS\ExtendedSearch\Source\PostProcessor\Base as PostProcessorBase;
use MediaWiki\MediaWikiServices;

class Base {

	/**
	 * @deprecated since version 3.1.13 instead of using $this->lookupModifiers
	 * to register the modifiers, use static::getAvailableLookupModifiers to
	 * add/substract/move your modifiers
	 * @var array
	 */
	protected $lookupModifiers = [
		Backend::QUERY_TYPE_SEARCH => [],
		Backend::QUERY_TYPE_AUTOCOMPLETE => []
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
	 * @param \BS\ExtendedSearch\Backend $oBackend
	 * @param array $aConfig
	 */
	public function __construct( $oBackend, $aConfig ) {
		$this->oBackend = $oBackend;
		$this->oConfig = new \MultiConfig( [
			MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' ),
			new \HashConfig( $aConfig )
		] );
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
	 * @deprecated since version 3.1.13 instead of using $this->lookupModifiers
	 * to register the modifiers, use static::getAvailableLookupModifiers to
	 * add/substract/move your modifiers
	 * @return array [ 'type' => [ 'modifierName1', 'modifierName2' ] ]
	 */
	private function getLegacyAvailableLookupModifiers() {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		$modifiers = [];
		if ( !empty( $this->lookupModifiers ) ) {
			foreach ( $this->lookupModifiers as $type => $legacyModifiers ) {
				if ( !isset( $modifiers[$type] ) ) {
					$modifiers[$type] = [];
				}
				foreach ( $legacyModifiers as $key => $legacyModifier ) {
					$modifiers[$type][] = is_string( $key ) ? $key : $legacyModifier;
				}
			}
		}
		return $modifiers;
	}

	/**
	 * @deprecated since version 3.1.13 - Use registry instead and implement
	 * ILookupModifier
	 * @param \BS\ExtendedSearch\Lookup $oLookup
	 * @param \IContextSource $oContext
	 * @param string $sType
	 * @return ILookupModifier[]
	 */
	public function getLookupModifiers( $oLookup, $oContext,
		$sType = Backend::QUERY_TYPE_SEARCH ) {
		if ( !isset( $this->getLegacyAvailableLookupModifiers()[$sType] ) ) {
			return [];
		}
		// deprecated since version 3.1.13
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		$factory = MediaWikiServices::getInstance()->getService(
			'BSExtendedSearchLookupModifierFactory'
		);
		$lookupModifiers = [];
		foreach ( $this->getLegacyAvailableLookupModifiers()[$sType] as $name ) {
			$lookupModifier = $factory->newFromName( $name, $oLookup, $oContext );
			if ( !$lookupModifier ) {
				if ( !isset( $this->lookupModifiers[$sType][$name] ) ) {
					continue;
				}
				if ( !class_exists( $this->lookupModifiers[$sType][$name] ) ) {
					continue;
				}
				$lookupModifier = new $this->lookupModifiers[$sType][$name](
					$oLookup,
					$oContext
				);
			}
			$lookupModifiers[] = $lookupModifier;
		}
		return $lookupModifiers;
	}

	/**
	 *
	 * @return array
	 */
	public function getIndexSettings() {
		// This kind of tokenizing breaks words in 3-char parts,
		// which makes it possible to match single words in compound words
		return [
			"settings" => [
				// Only for testing purposes on small sample, remove or increase for production
				// "number_of_shards" => 1,
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
							// Change
							"tokenizer" => "standard",
							"filter" => [
								"lowercase",
								"autocomplete_filter"
							]
						]
					],
					"normalizer" => [
						"lowercase" => [
							"type" => "custom",
							"char_filter" => [],
							"filter" => [ "lowercase", "asciifolding" ]
						]
					]
				]
			]
		];
	}

	/**
	 *
	 * @param \Elastica\Client $client
	 */
	public function runAdditionalSetupRequests( \Elastica\Client $client ) {
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
		foreach ( $aDocumentConfigs as $aDC ) {
			$aDocs[] = new \Elastica\Document( $aDC['id'], $aDC );
		}

		$oResult = $oType->addDocuments( $aDocs );
		if ( !$oResult->isOk() ) {
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

		if ( !$oResult->isOk() ) {
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
	 * @return Formatter\Base
	 */
	public function getFormatter() {
		return new Formatter\Base( $this );
	}

	/**
	 *
	 * @return string
	 */
	public function getSearchPermission() {
		// Default - no permission required
		return '';
	}

	/**
	 * @param PostProcessor $base
	 * @return IPostProcessor
	 */
	public static function getPostProcessor( $base ) {
		return PostProcessorBase::factory( $base );
	}

	/**
	 * Can fields in this source be used for sorting
	 *
	 * @return bool
	 */
	public function isSortable() {
		return true;
	}
}
