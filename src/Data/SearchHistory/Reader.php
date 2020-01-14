<?php

namespace BS\ExtendedSearch\Data\SearchHistory;

use BlueSpice\Data\DatabaseReader;
use BlueSpice\Data\ReaderParams;
use IContextSource;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends DatabaseReader {
	/**
	 *
	 * @param ILoadBalancer $loadBalancer
	 * @param IContextSource|null $context
	 */
	public function __construct( $loadBalancer, IContextSource $context = null ) {
		parent::__construct( $loadBalancer, $context, $context->getConfig() );
	}

	/**
	 *
	 * @param ReaderParams $params
	 * @return PrimaryDataProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->db, $this->getSchema() );
	}

	/**
	 *
	 * @return null
	 */
	protected function makeSecondaryDataProvider() {
		return null;
	}

	/**
	 *
	 * @return Schema
	 */
	public function getSchema() {
		return new Schema;
	}

}
