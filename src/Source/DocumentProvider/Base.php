<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use BS\ExtendedSearch\ISearchDocumentProvider;

class Base implements ISearchDocumentProvider {

	/**
	 * @inheritDoc
	 */
	public function getDocumentData( string $uri, string $documentId, $dataItem ): array {
		return [
			'id' => $documentId,
			'sortable_id' => $documentId,
			'uri' => $uri,
			'basename' => wfBaseName( $uri ),
			'basename_exact' => wfBaseName( $uri ),
			'suggestions' => wfBaseName( $uri ),
		];
	}
}
