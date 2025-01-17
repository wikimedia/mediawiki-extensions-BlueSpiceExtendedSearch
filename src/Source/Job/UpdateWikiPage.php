<?php

namespace BS\ExtendedSearch\Source\Job;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use WikiPage;

class UpdateWikiPage extends UpdateTitleBase {

	/** @inheritDoc */
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
			$documentId = $this->getDocumentId( $this->getDocumentProviderUri() );
			$this->getSource()->deleteDocumentFromIndex( $documentId );

			return [ 'id' => $documentId ];
		}

		return parent::doRun();
	}

	/**
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function skipProcessing() {
		return in_array(
			$this->getTitle()->getNamespace(),
			$this->getSource()->getConfig()->get( 'skip_namespaces' )
		);
	}

	/**
	 *
	 * @return WikiPage|null
	 */
	protected function getDocumentProviderSource() {
		return MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $this->getTitle() );
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	protected function isNoIndex() {
		$dp = $this->getSource()->getDocumentProvider();
		$pageProps = $dp->getPageProps( $this->getTitle() );

		return isset( $pageProps['noindex'] );
	}
}
