<?php

namespace BS\ExtendedSearch\Data\TagCloud\Searchstats;

use BlueSpice\Services;
use BlueSpice\Data\DatabaseReader;
use BlueSpice\TagCloud\Data\TagCloud\Schema;

class Reader extends DatabaseReader {
	/**
	 *
	 * @param \LoadBalancer $loadBalancer
	 * @param \IContextSource $context
	 */
	public function __construct( $loadBalancer, \IContextSource $context = null ) {
		parent::__construct( $loadBalancer, $context, $context->getConfig() );
	}

	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->db, $this->context );
	}

	protected function makeSecondaryDataProvider() {
		return new SecondaryDataProvider(
			Services::getInstance()->getLinkRenderer(),
			$this->context
		);
	}

	public function getSchema() {
		return new Schema();
	}

}
