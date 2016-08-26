<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class DecoratorBase extends Base {
	/**
	 *
	 * @var Base
	 */
	protected $oDecoratedDP = null;

	/**
	 *
	 * @param Base $oDecoratedDP
	 */
	public function __construct( $oDecoratedDP ) {
		$this->oDecoratedDP = $oDecoratedDP;
	}

	/**
	 * Provides a array of data that will be written to the search index.
	 * $mDataItem is whatever thingie that needs to be indexed
	 * @param string $sUri
	 * @param mixed $mDataItem
	 * @return array
	 */
	public function getDataConfig( $sUri, $mDataItem ) {
		return $this->oDecoratedDP->getDataConfig( $sUri, $mDataItem );
	}
}