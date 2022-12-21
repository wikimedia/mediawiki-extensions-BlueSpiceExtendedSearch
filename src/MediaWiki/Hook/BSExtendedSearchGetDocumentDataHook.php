<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

use BS\ExtendedSearch\Source\DocumentProvider\Base as DocumentProvider;

interface BSExtendedSearchGetDocumentDataHook {
	/**
	 * @param DocumentProvider $documentProvider
	 * @param array &$data
	 * @param string $uri
	 * @param mixed $documentProviderSource
	 *
	 * @return mixed
	 */
	public function onBSExtendedSearchGetDocumentData( $documentProvider, &$data, $uri, $documentProviderSource );
}
