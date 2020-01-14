<?php

namespace BS\ExtendedSearch\Data\SearchHistory;

use BlueSpice\Data\IStore;
use BlueSpice\Data\ReaderParams;
use BlueSpice\Services;
use IContextSource;
use RequestContext;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {

	/**
	 *
	 * @var IContextSource
	 */
	protected $context = null;

	/**
	 *
	 * @var ILoadBalancer
	 */
	protected $lb = null;

	/**
	 *
	 * @param IContextSource|null $context
	 */
	public function __construct( IContextSource $context = null ) {
		if ( !$context ) {
			$context = RequestContext::getMain();
		}
		$this->context = $context;
		$this->loadBalancer = Services::getInstance()->getDBLoadBalancer();
	}

	/**
	 *
	 * @return Reader
	 */
	public function getReader() {
		return new Reader( $this->loadBalancer, $this->context );
	}

	/**
	 *
	 * @return Writer
	 */
	public function getWriter() {
		return new Writer(
			$this->getReader(),
			$this->loadBalancer,
			$this->context
		);
	}

	/**
	 *
	 * @param array $params
	 * @return ReaderParams
	 */
	public function makeReaderParams( array $params = [] ) {
		return new ReaderParams( $params );
	}
}
