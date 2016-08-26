<?php

namespace BS\ExtendedSearch\Source;

class Base {

	/**
	 *
	 * @var \Elastica\Index
	 */
	protected $oIndex = null;

	protected $aConfig = array();

	/**
	 *
	 * @param \Elastica\Index
	 * @param array $aConfig
	 */
	public function __construct( $oIndex, $aConfig ) {
		$this->oIndex = $oIndex;
		$this->aConfig = $aConfig;
		$oConfig = new \HashConfig( $aConfig );
	}

	public function getTypeKey() {
		return '';
	}
	public function makeMappingPropertyConfig() {
		return array(
			'uri' => array(
				'type' => 'string',
				'include_in_all' => false
			),
			'title' => array(
				'type' => 'string'
			),
			'content' => array(
				'type' => 'string'
			),
			'tags' => array(
				'type' => 'string'
			),
			'cdate' => array(
				'type' => 'date'
			),
			'size' => array(
				'type' => 'double'
			)
		);
	}

	/**
	 * @return BS\ExtendedSearch\Crawler\Base
	 */
	public function getCrawler() {
		return new Crawler\Base( $this->oIndex, $this->getCrawlerConfig() );
	}

	protected function getCrawlerConfig() {
		return isset( $this->aConfig['crawler'] ) ? $this->aConfig['crawler'] : [];
	}

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

	public function makeDocumentConfig( $sURI, $mSourceEntity ) {
		return [
			'_id' => md5( $sURI ), //Full qualified URI to document
		];
	}

	#abstract public function getFormatter();
}