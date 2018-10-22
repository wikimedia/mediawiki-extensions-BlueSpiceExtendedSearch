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

	public function run() {
		$skipNamespaces = $this->getSource()->getConfig()->get( 'skip_namespaces' );
		if( in_array( $this->getTitle()->getNamespace(), $skipNamespaces ) ) {
			return true;
		}
		parent::run();
	}

	protected function getDocumentProviderSource() {
		return \WikiPage::factory( $this->getTitle() );
	}
}