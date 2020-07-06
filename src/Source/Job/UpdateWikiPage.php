<?php

namespace BS\ExtendedSearch\Source\Job;

use PageProps;
use Title;

class UpdateWikiPage extends UpdateTitleBase {

	protected $sSourceKey = 'wikipage';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		parent::__construct( 'updateWikiPageIndex', $title, $params );
	}

	protected function doRun() {
		$this->dp = $this->getSource()->getDocumentProvider();
		if ( $this->isNoIndex() ) {
			$this->getSource()->deleteDocumentsFromIndex(
				[ $this->dp->getDocumentId( $this->getDocumentProviderUri() ) ]
			);
			$id = $this->dp->getDocumentId( $this->getDocumentProviderUri() );
			return [ 'id' => $id ];
		}

		return parent::doRun();
	}

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		$noindex = (bool)PageProps::getInstance()
			->getProperties( $this->title, 'noindex' );
		if ( $noindex ) {
			return true;
		}
		return in_array(
			$this->getTitle()->getNamespace(),
			$this->getSource()->getConfig()->get( 'skip_namespaces' )
		);
	}

	/**
	 *
	 * @return \Wikipage
	 */
	protected function getDocumentProviderSource() {
		return \WikiPage::factory( $this->getTitle() );
	}

	protected function isNoIndex() {
		$dp = $this->getSource()->getDocumentProvider();
		$pageProps = $dp->getPageProps( $this->getTitle() );
		return isset( $pageProps['noindex'] );
	}
}
