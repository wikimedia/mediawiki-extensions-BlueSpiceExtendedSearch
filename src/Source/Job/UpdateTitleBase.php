<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateTitleBase extends UpdateBase {

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
			$this->getSource()->deleteDocumentsFromIndex(
				[ $this->dp->getDocumentId( $this->getDocumentProviderUri() ) ]
			);
			$id = $this->dp->getDocumentId( $this->getDocumentProviderUri() );
			return [ 'id' => $id ];
		}

		$providerSource = $this->getDocumentProviderSource();
		if ( !$providerSource ) {
			return [];
		}

		$aDC = $this->dp->getDataConfig(
			$this->getDocumentProviderUri(),
			$providerSource
		);
		$this->getHookContainer()->run(
			'BSExtendedSearchGetDocumentData',
			[
				$this->dp,
				&$aDC,
				$this->getDocumentProviderUri(),
				$providerSource
			]
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
