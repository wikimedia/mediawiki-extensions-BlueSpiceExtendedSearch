<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateSpecialPage extends UpdateWikiPage {

	protected $sSourceKey = 'specialpage';

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		parent::__construct( 'updateSpecialPageIndex', $title, $params );
	}

	protected function getDocumentProviderSource() {
		return \SpecialPageFactory::getPage( $this->getTitle()->getText() );
	}
}
