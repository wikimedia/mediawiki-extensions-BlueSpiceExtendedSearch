<?php

namespace BS\ExtendedSearch\Source\Job;

use Exception;
use MediaWiki\Title\Title;

class UpdateExternalFile extends UpdateJob {
	/** @inheritDoc */
	protected $sSourceKey = 'externalfile';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		parent::__construct( 'updateExternalFileIndex', $title, $params );
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function doRun() {
		$this->dp = $this->getSource()->getDocumentProvider();
		$oFile = new \SplFileInfo( $this->params['src'] );

		if ( $this->isDeletion() ) {
			$documentId = $this->getDocumentId( $this->params['dest'] );
			$this->getSource()->deleteDocumentFromIndex( $documentId );

			return [ 'id' => $documentId ];
		}
		$aDC = $this->dp->getDocumentData(
			$this->params['dest'], $this->getDocumentId( $this->params['dest'] ), $oFile
		);

		$this->getSource()->addDocumentToIndex( $aDC );
		return $aDC;
	}

	/**
	 * @return bool
	 */
	protected function isDeletion() {
		$file = new \SplFileInfo( $this->params['src'] );
		return !file_exists( $file->getPathname() );
	}

}
