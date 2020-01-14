<?php

namespace BS\ExtendedSearch\Data;

use BlueSpice\Data\ISecondaryDataProvider;
use BlueSpice\Data\ReaderParams;
use BlueSpice\Data\ResultSet;
use BS\ExtendedSearch\Backend;
use Config;
use IContextSource;

abstract class Reader extends \BlueSpice\Data\Reader {

	/**
	 *
	 * @var Backend
	 */
	protected $searchBackend = null;

	/**
	 *
	 * @param Backend $searchBackend
	 * @param IContextSource|null $context
	 * @param Config|null $config
	 */
	public function __construct( Backend $searchBackend,
		IContextSource $context = null, Config $config = null ) {
		parent::__construct( $context, $config );
		$this->searchBackend = $searchBackend;
	}

	/**
	 *
	 * @param ReaderParams $params
	 * @return PrimaryDataProvider
	 */
	abstract protected function makePrimaryDataProvider( $params );

	/**
	 *
	 * @param ReaderParams $params
	 * @return ResultSet
	 */
	public function read( $params ) {
		$primaryDataProvider = $this->makePrimaryDataProvider( $params );
		$dataSets = $primaryDataProvider->makeData( $params );

		$secondaryDataProvider = $this->makeSecondaryDataProvider();
		if ( $secondaryDataProvider instanceof ISecondaryDataProvider ) {
			$dataSets = $secondaryDataProvider->extend( $dataSets );
		}

		$resultSet = new ResultSet( $dataSets, 0 );
		return $resultSet;
	}
}
