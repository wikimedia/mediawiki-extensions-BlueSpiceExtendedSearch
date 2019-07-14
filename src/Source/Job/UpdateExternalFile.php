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
		$this->dp = $this->getSource()->getDocumentProvider();
		$oFile = new \SplFileInfo( $this->params['src'] );

		if ( $this->isDeletion() ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $this->dp->getDocumentId( $this->params['dest'] ) ]
			);
			$id = $this->dp->getDocumentId( $this->params['dest'] );
			return [ 'id' => $id ];
		}
		$aDC = $this->dp->getDataConfig( $this->params['dest'], $oFile );
		$this->getSource()->addDocumentsToIndex( [ $aDC ] );
		return $aDC;
	}

	protected function isDeletion() {
		$file = new \SplFileInfo( $this->params['src'] );
		return !file_exists( $file->getPathname() );
	}

}
