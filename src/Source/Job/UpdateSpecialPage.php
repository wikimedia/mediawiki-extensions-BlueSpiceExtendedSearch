<?php

namespace BS\ExtendedSearch\Source\Job;

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class UpdateSpecialPage extends UpdateTitleBase {

	/** @inheritDoc */
	protected $sSourceKey = 'specialpage';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [] ) {
		parent::__construct( 'updateSpecialPageIndex', $title, $params );
	}

	/**
	 *
	 * @return SpecialPage|null
	 */
	protected function getDocumentProviderSource() {
		return \MediaWiki\MediaWikiServices::getInstance()
			->getSpecialPageFactory()
			->getPage( $this->getTitle()->getText() );
	}

	public function doRun() {
		// We need to override UpdateTitleBase::run because as SpecialPage
		// title does never "exist" in the database
		$oDP = $this->getSource()->getDocumentProvider();
		$aDC = $oDP->getDocumentData(
			$this->getDocumentProviderUri(),
			$this->getDocumentId( $this->getDocumentProviderUri() ),
			$this->getDocumentProviderSource()
		);
		$this->getSource()->addDocumentToIndex( $aDC );
		return $aDC;
	}

	/**
	 *
	 * @return bool
	 */
	protected function isDeletion() {
		return false;
	}
}
