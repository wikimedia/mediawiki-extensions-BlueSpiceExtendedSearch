<?php

namespace BS\ExtendedSearch\Source\Job;

use BS\ExtendedSearch\Plugin\IDocumentDataModifier;

class UpdateTitleBase extends UpdateJob {

	/**
	 * TODO: In order to be able to index multiple versions of the page,
	 * this needs to go to the document provider
	 *
	 * @return string
	 */
	protected function getDocumentProviderUri() {
		if ( isset( $this->params['canonicalUrl'] ) ) {
			return $this->params['canonicalUrl'];
		}
		return $this->getTitle()->getCanonicalURL();
	}

	protected function doRun() {
		$this->dp = $this->getSource()->getDocumentProvider();
		if ( $this->isDeletion() ) {
			$documentId = $this->getDocumentId( $this->getDocumentProviderUri() );

			try {
				$this->getSource()->deleteDocumentFromIndex( $documentId );
			} catch ( \Exception $e ) {
				$this->setLastError( $e->getMessage() );
			}

			return [
				'id' => $documentId,
				'prefixed_title' => $this->getTitle()->getPrefixedText()
			];
		}

		$providerSource = $this->getDocumentProviderSource();
		if ( !$providerSource ) {
			return [];
		}

		$aDC = $this->dp->getDocumentData(
			$this->getDocumentProviderUri(),
			$this->getDocumentId( $this->getDocumentProviderUri() ),
			$providerSource
		);
		$plugins = $this->getBackend()->getPluginsForInterface( IDocumentDataModifier::class );
		foreach ( $plugins as $plugin ) {
			$plugin->modifyDocumentData( $this->dp, $aDC, $this->getDocumentProviderUri(), $providerSource );
		}

		$this->getSource()->addDocumentToIndex( $aDC );
		return $aDC;
	}

	/**
	 *
	 * @return bool
	 */
	protected function isDeletion() {
		$force = isset( $this->params['forceDelete'] ) && $this->params['forceDelete'] === true;
		return $force ||
			( !$this->getTitle()->exists() || $this->action == static::ACTION_DELETE );
	}
}
