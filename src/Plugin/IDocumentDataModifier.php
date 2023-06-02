<?php

namespace BS\ExtendedSearch\Plugin;

use BS\ExtendedSearch\ISearchDocumentProvider;

interface IDocumentDataModifier {
	/**
	 * @param ISearchDocumentProvider $documentProvider
	 * @param array &$data
	 * @param string $uri
	 * @param mixed $documentProviderSource
	 *
	 * @return mixed
	 */
	public function modifyDocumentData(
		ISearchDocumentProvider $documentProvider, array &$data, $uri, $documentProviderSource
	): void;
}
