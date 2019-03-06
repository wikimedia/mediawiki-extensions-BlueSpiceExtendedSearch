<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateTitleBase extends UpdateBase {

	protected function getDocumentProviderUri() {
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

	protected function isDeletion() {
		return !$this->getTitle()->exists() || $this->action == static::ACTION_DELETE;
	}

}
