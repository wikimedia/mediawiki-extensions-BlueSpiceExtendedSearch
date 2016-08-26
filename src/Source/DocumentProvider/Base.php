<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class Base {
	public function getDocumentId( $sUri ) {
		return md5( $sUri );
	}

	public function getDataConfig( $sUri, $mDataItem ) {
		return [
			'id' => $this->getDocumentId( $sUri ),
			'uri' => $sUri
		];
	}
}