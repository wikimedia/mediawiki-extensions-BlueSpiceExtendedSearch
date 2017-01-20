<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateTitleBase extends UpdateBase {
	public function run() {
		$oDP = $this->getSource()->getDocumentProvider();

		if( !$this->getTitle()->exists() ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $oDP->getDocumentId( $this->getTitle()->getCanonicalURL() ) ]
			);
		}
		else {
			$aDC = $oDP->getDataConfig(
				$this->getDocumentProviderUri(),
				$this->getDocumentProviderSource()
			);
			$this->getSource()->addDocumentsToIndex( [ $aDC ] );
		}
	}

	protected function getDocumentProviderUri() {
		return $this->getTitle()->getFullURL();
	}

}