<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateTitleBase extends UpdateBase {
	public function run() {
		$oDP = $this->getSource()->getDocumentProvider();
		if ( !$this->getTitle()->exists() || $this->action == static::ACTION_DELETE ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $oDP->getDocumentId( $this->getDocumentProviderUri() ) ]
			);
		} else if( $this->action == static::ACTION_UPDATE ) {
			$aDC = $oDP->getDataConfig(
				$this->getDocumentProviderUri(),
				$this->getDocumentProviderSource()
			);
			$this->getSource()->addDocumentsToIndex( [ $aDC ] );
		}
	}

	protected function getDocumentProviderUri() {
		return $this->getTitle()->getCanonicalURL();
	}

}