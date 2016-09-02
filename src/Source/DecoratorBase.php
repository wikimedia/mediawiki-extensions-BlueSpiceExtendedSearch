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
}