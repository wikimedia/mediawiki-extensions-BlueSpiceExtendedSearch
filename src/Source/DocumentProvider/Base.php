<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class Base {

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
