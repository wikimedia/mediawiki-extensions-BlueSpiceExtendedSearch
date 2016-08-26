<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class Base {
	public function getDataConfig( $sUri, $mDataItem ) {
		return [
			'id' => md5( $sUri ),
			'uri' => $sUri
		];
	}
}