<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use MediaWiki\MediaWikiServices;

class Base {

	/** @var MediaWikiServices */
	protected $services = null;

	public function __construct() {
		$this->services = MediaWikiServices::getInstance();
	}

	/**
	 *
	 * @param string $sUri
	 * @return string
	 */
	public function getDocumentId( $sUri ) {
		return md5( $sUri );
	}

	/**
	 *
	 * @param string $sUri
	 * @param mixed $mDataItem
	 * @return array
	 */
	public function getDataConfig( $sUri, $mDataItem ) {
		return [
			'id' => $this->getDocumentId( $sUri ),
			'sortable_id' => $this->getDocumentId( $sUri ),
			'uri' => $sUri,
			'basename' => wfBaseName( $sUri ),
			'basename_exact' => wfBaseName( $sUri )
		];
	}

	public function __destruct() {
	}
}
