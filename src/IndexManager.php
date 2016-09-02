<?php

namespace BS\ExtendedSearch;

class IndexManager {

	/**
	 *
	 * @var string
	 */
	protected $sIndexName = '';

	/**
	 *
	 * @var Source\Base[]
	 */
	protected $aSources = [];

	/**
	 *
	 * @var \Elastica\Client
	 */
	protected $oClient = null;


	/**
	 *
	 * @param string $sIndexName
	 * @param \Elastica\Client $oClient
	 * @param  Source\Base[] $aSources
	 */
	public function __construct( $sIndexName, $oClient, $aSources = [] ) {
		$this->sIndexName = $sIndexName;
		$this->oClient = $oClient;
		$this->aSources = $aSources;
	}

	public function delete() {
		$this->oClient->getIndex( $this->sIndexName )->delete();
	}

	public function create() {
		$aIndexSettings = $this->getBaseSettings();
		foreach( $this->aSources as $oSource ) {
			$aIndexSettings = array_merge_recursive(
				$aIndexSettings,
				$oSource->getIndexSettings()
			);
		}

		$oIndex = $this->oClient->getIndex( $this->sIndexName );
		$oResponse = $oIndex->create( $aIndexSettings );

		foreach( $this->aSources as $oSource ) {
			$oType = $oIndex->getType( $oSource->getTypeKey() );
			$oMapping = new \Elastica\Type\Mapping();
			$oMapping->setType( $oType );
			$oMappingProvider = $oSource->getMappingProvider();
			$oMapping->setProperties( $oMappingProvider->getPropertyConfig() );

			$oResponse2 = $oMapping->send();
		}
	}

	public function addDocuments( $aDocumentConfigs, $sTypeKey ) {
		$oElasticaIndex = $this->oClient->getIndex( $this->sName );
		$oType = $oElasticaIndex->getType( $sTypeKey );
		foreach( $aDocumentConfigs as $aDC ) {
			$oType->addDocument(
				new \Elastica\Document( $aDC['id'], $aDC )
			);
			$oElasticaIndex->refresh();
		}
	}

	public function deleteDocuments( $aDocumentConfigs, $sTypeKey ) {
		//TODO: implement
	}

	protected function getBaseSettings() {
		//TODO: Add good base settings
		return [
			'analysis' => [
				'analyzer' => [
					'default_index' => [
						'type' => 'custom',
						'tokenizer' => 'standard',
						'filter' => ['lowercase' ]
					],
					'default_search' => [
						'type' => 'custom',
						'tokenizer' => 'standard',
						'filter' => [ 'standard', 'lowercase' ]
					]
				]
			]
		];
	}
}
