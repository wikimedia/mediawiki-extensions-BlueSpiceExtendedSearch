<?php

namespace BS\ExtendedSearch\Source;

class Base {

	/**
	 *
	 * @var \Config
	 */
	protected $oConfig = null;

	/**
	 *
	 * @param \Elastica\Index
	 * @param array $aConfig
	 */
	public function __construct( $aConfig ) {
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
	 * @return string
	 */
	public function getTypeKey() {
		return '';
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
	 * @return array
	 */
	public function getIndexSettings() {
		return [];
	}

	#abstract public function getFormatter();
}