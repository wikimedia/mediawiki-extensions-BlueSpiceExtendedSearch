<?php

namespace BS\ExtendedSearch\MediaWiki\OOUI;

class CenterLayout extends \OOUI\Layout {
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Initialization
		$this->addClasses( [ 'bs-extendedsearch-center-layout' ] );
		if ( isset( $config['items'] ) ) {
			$this->appendContent( $config['items'] );
		}
	}
}
