<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateWikiPage extends \Job {
	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		parent::__construct( 'updateWikiPageIndex', $title, $params );
	}

	public function run() {
		
	}
}