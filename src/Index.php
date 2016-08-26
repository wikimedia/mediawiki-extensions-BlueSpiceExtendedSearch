<?php

namespace BS\ExtendedSearch;

class Index {

	/**
	 *
	 * @var \Elastica\Client
	 */
	protected $oClient = null;

	/**
	 *
	 * @var string
	 */
	protected $sName = '';

	/**
	 *
	 * @var Source[]
	 */
	protected $aSources = [];

	public function __construct( $aConfig ) {
		//TODO: Throw some nice exceptions
		$this->sName = $this->makeName();
		$this->makeClient( $aConfig['connection'] );
		$this->makeSources( $aConfig['sources'] );
	}

	protected function makeName() {
		return wfWikiID();
	}

	/**
	 *
	 * @return Source\Base[]
	 */
	public function getSources() {
		return $this->aSources;
	}

	/**
	 *
	 * @param string $sSourceKey
	 * @return Source\Base
	 */
	public function getSource( $sSourceKey ) {
		return $this->aSources[ $sSourceKey ]; //TODO: Throw exception or return NullSource if key not set?
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

	protected function makeClient( $aConfig ) {
		$this->oClient = new \Elastica\Client( $aConfig );
	}

	protected function makeSources( $aSourceDefinitions ) {
		foreach( $aSourceDefinitions as $sSourceKey => $aSourceDefinition ) {
			$aConfig = isset( $aSourceDefinition['config'] ) ? $aSourceDefinition['config'] : [];
			$oElasticaIndex = $this->oClient->getIndex( $this->sName );

			$oSource = new $aSourceDefinition['class'](
				new Source\Base( $oElasticaIndex, $aConfig )
			);

			\Hooks::run( 'BSExtendedSearchMakeSource', [ &$oSource, $oElasticaIndex, $aConfig ] );

			$this->aSources[$sSourceKey] = $oSource;
		}
	}

	public function create(){
		$oIdx = $this->oClient->getIndex( $this->sName );
		$oResponse = $oIdx->create( $this->makeIndexConfig(), true );

		foreach( $this->getSources() as $oSource ) {
			$oType = $oIdx->getType( $oSource->getTypeKey() );
			$oMapping = new \Elastica\Type\Mapping();
			$oMapping->setType( $oType );
			$aMappingProperties = $oSource->makeMappingPropertyConfig();
			$oMapping->setProperties( $aMappingProperties );

			$oResponse2 = $oMapping->send();
		}
	}

	protected function makeIndexConfig() {
		return array(
			'analysis' => array(
				'analyzer' => array(
					'default_index' => array(
						'type' => 'custom',
						'tokenizer' => 'standard',
						'filter' => array( 'lowercase' )
					),
					'default_search' => array(
						'type' => 'custom',
						'tokenizer' => 'standard',
						'filter' => array('standard', 'lowercase')
					)
				)
			)
		);
	}

}