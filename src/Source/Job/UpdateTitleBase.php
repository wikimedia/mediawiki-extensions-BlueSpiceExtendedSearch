<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateTitleBase extends UpdateBase {

	protected function getDocumentProviderUri() {
		return $this->getTitle()->getCanonicalURL();
	}

	protected function doRun() {
		$oDP = $this->getSource()->getDocumentProvider();
		if ( $this->isDeletion() ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $oDP->getDocumentId( $this->getDocumentProviderUri() ) ]
			);
			return [ 'id' => $oDP->getDocumentId( $this->getDocumentProviderUri() ) ];
		}
		$aDC = $oDP->getDataConfig(
			$this->getDocumentProviderUri(),
			$this->getDocumentProviderSource()
		);
		$this->getSource()->addDocumentsToIndex( [ $aDC ] );
		return $aDC;
	}

	protected function isDeletion() {
		return !$this->getTitle()->exists() || $this->action == static::ACTION_DELETE;
	}

}
