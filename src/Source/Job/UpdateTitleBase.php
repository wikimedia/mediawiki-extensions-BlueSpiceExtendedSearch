<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateTitleBase extends UpdateBase {

	/**
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
			$this->getSource()->deleteDocumentsFromIndex(
				[ $this->dp->getDocumentId( $this->getDocumentProviderUri() ) ]
			);
			$id = $this->dp->getDocumentId( $this->getDocumentProviderUri() );
			return [ 'id' => $id ];
		}

		$aDC = $this->dp->getDataConfig(
			$this->getDocumentProviderUri(),
			$this->getDocumentProviderSource()
		);
		$this->getSource()->addDocumentsToIndex( [ $aDC ] );
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
