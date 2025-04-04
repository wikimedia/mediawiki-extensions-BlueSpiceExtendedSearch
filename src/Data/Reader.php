<?php

namespace BS\ExtendedSearch\Data;

use BS\ExtendedSearch\Backend;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\Reader as BaseReader;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\ResultSet;

abstract class Reader extends BaseReader {

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
	public function __construct( Backend $searchBackend, ?IContextSource $context = null, ?Config $config = null ) {
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
