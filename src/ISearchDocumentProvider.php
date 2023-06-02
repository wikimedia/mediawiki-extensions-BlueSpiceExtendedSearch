<?php

namespace BS\ExtendedSearch;

interface ISearchDocumentProvider {

	/**
	 *
	 * @param string $uri
	 * @param string $documentId
	 * @param mixed $dataItem
	 *
	 * @return array
	 */
	public function getDocumentData( string $uri, string $documentId, $dataItem ): array;
}
