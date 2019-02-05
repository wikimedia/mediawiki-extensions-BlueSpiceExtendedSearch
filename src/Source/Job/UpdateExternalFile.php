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

	public function doRun() {
		$oDP = $this->getSource()->getDocumentProvider();
		$oFile = new \SplFileInfo( $this->params['src'] );

		if( $this->isDeletion() ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $oDP->getDocumentId( $this->params['dest'] ) ]
			);
			return [ 'id' => $oDP->getDocumentId( $this->params['dest'] ) ];
		}
		$aDC = $oDP->getDataConfig(	$this->params['dest'], $oFile );
		$this->getSource()->addDocumentsToIndex( [ $aDC ] );
		return $aDC;
	}

	protected function isDeletion() {
		$file = new \SplFileInfo( $this->params['src'] );
		return !file_exists( $file->getPathname() );
	}

}
