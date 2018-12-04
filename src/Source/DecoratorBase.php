<?php

namespace BS\ExtendedSearch\Source;

class DecoratorBase extends Base {

	/**
	 *
	 * @var Base
	 */
	protected $oDecoratedSource = null;

	/**
	 *
	 * @param Base $oSource
	 */
	public function __construct( $oSource ) {
		$this->oDecoratedSource = $oSource;
	}

	/**
	 * @param Base $base
	 * @return DecoratorBase
	 */
	public static function create( $base ) {
		return new self( $base );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Backend
	 */
	public function getBackend() {
		return $this->oDecoratedSource->getBackend();
	}

	/**
	 *
	 * @return Config
	 */
	public function getConfig() {
		return $this->oDecoratedSource->getConfig();
	}

	/**
	 *
	 * @return MappingProvider\Base
	 */
	public function getMappingProvider() {
		return $this->oDecoratedSource->getMappingProvider();
	}

	/**
	 *
	 * @return Crawler\Base
	 */
	public function getCrawler() {
		return $this->oDecoratedSource->getCrawler();
	}

	/**
	 *
	 * @return DocumentProvider\Base
	 */
	public function getDocumentProvider() {
		return $this->oDecoratedSource->getDocumentProvider();
	}

	/**
	 *
	 * @param \IContextSource $oContext
	 * @return BS\ExtendedSearch\Source\QueryProcessor\Base[]
	 */
	public function getQueryProcessors( $oContext)  {
		return $this->oDecoratedSource->getQueryProcessors( $oContext );
	}

	/**
	 *
	 * @return string
	 */
	public function getTypeKey() {
		return $this->oDecoratedSource->getTypeKey();
	}

	/**
	 *
	 * @return array
	 */
	public function getIndexSettings() {
		return $this->oDecoratedSource->getIndexSettings();
	}

	/**
	 *
	 * @return Updater\Base
	 */
	public function getUpdater() {
		return $this->oDecoratedSource->getUpdater();
	}

	public function getFormatter () {
		return $this->oDecoratedSource->getFormatter();
	}

}