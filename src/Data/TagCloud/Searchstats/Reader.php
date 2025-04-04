<?php

namespace BS\ExtendedSearch\Data\TagCloud\Searchstats;

use BlueSpice\TagCloud\Data\TagCloud\Schema;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DataStore\DatabaseReader;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;

class Reader extends DatabaseReader {
	/**
	 *
	 * @param \Wikimedia\Rdbms\LoadBalancer $loadBalancer
	 * @param IContextSource|null $context
	 */
	public function __construct( $loadBalancer, ?IContextSource $context = null ) {
		parent::__construct( $loadBalancer, $context, $context->getConfig() );
	}

	/**
	 *
	 * @param ReaderParams $params
	 * @return PrimaryDataProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->db, $this->context );
	}

	/**
	 *
	 * @return SecondaryDataProvider
	 */
	protected function makeSecondaryDataProvider() {
		return new SecondaryDataProvider(
			MediaWikiServices::getInstance()->getLinkRenderer(),
			$this->context
		);
	}

	/**
	 *
	 * @return Schema
	 */
	public function getSchema() {
		return new Schema();
	}

}
