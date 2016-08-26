<?php

namespace BS\ExtendedSearch\Source\Job;

class UpdateSpecialPage extends UpdateBase {
	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		parent::__construct( 'updateSpecialPageIndex', $title, $params );
	}

	public function run() {
		#$oType = \BS\ExtendedSearch\Indices::factory('local')->
	}
}
