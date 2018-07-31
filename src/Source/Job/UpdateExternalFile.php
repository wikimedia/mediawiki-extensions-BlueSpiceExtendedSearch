<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateExternalFile extends UpdateBase {
	protected $sSourceKey = 'externalfile';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		parent::__construct( 'updateExternalFileIndex', $title, $params );
	}

	public function run() {
		$oDP = $this->getSource()->getDocumentProvider();
		$oFile = new \SplFileInfo( $this->params['src'] );

		if( !file_exists( $oFile->getPathname() ) ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $oDP->getDocumentId( $this->params['dest'] ) ]
			);
		}
		else {
			$aDC = $oDP->getDataConfig(	$this->params['dest'], $oFile );
			$this->getSource()->addDocumentsToIndex( [ $aDC ] );
		}
	}
}