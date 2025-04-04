<?php

namespace BS\ExtendedSearch\Data;

use BS\ExtendedSearch\Backend;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DataStore\IStore;

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
	public function __construct( ?Backend $searchBackend = null ) {
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
		$this->searchBackend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
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
