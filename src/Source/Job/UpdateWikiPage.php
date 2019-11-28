<?php

namespace BS\ExtendedSearch\Source\Job;

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

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
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

}
