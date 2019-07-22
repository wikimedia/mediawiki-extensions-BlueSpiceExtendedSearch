<?php

namespace BS\ExtendedSearch\Data;

use BlueSpice\Data\IStore;
use BS\ExtendedSearch\Backend;

abstract class Store implements IStore {

	/**
	 *
	 * @var Backend
	 */
	protected $searchBackend = null;

	/**
	 *
	 * @param Backend|null $searchBackend
	 */
	public function __construct( Backend $searchBackend = null ) {
		$this->searchBackend = $searchBackend;
	}

	/**
	 *
	 * @return Backend
	 */
	protected function getSearchBackend() {
		if ( $this->searchBackend ) {
			return $this->searchBackend;
		}
		$this->searchBackend = Backend::instance(
			$this->getSearchBackendKey()
		);
		return $this->searchBackend;
	}

	/**
	 *
	 * @return string
	 */
	protected function getSearchBackendKey() {
		return 'local';
	}
}
