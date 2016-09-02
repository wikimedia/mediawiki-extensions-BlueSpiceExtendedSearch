<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateWikiPage extends UpdateBase {

	protected $sSourceKey = 'wikipage';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		parent::__construct( 'updateWikiPageIndex', $title, $params );
	}

	public function run() {
		$oDP = $this->getSource()->getDocumentProvider();

		if( !$this->getTitle()->exists() ) {
			$this->getIndex()->deleteDocuments(
				[ $oDP->getDocumentId( $this->getTitle()->getCanonicalURL() ) ],
				$this->getSource()->getTypeKey()
			);
		}
		else {
			$aDC = $oDP->getDataConfig(
				$this->getTitle()->getCanonicalURL(),
				$this->getDocumentProviderSource()
			);
			$this->getIndex()->addDocuments( [ $aDC ], $this->getSource()->getTypeKey() );
		}
	}

	protected function getDocumentProviderSource() {
		return \WikiPage::factory( $this->getTitle() );
	}

}