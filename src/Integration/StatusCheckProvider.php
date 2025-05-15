<?php

namespace BS\ExtendedSearch\Integration;

use BlueSpice\InstanceStatus\IApiStatusProvider;
use BS\ExtendedSearch\Backend;
use Throwable;

class StatusCheckProvider implements IApiStatusProvider {

	/** @var Backend */
	private $backend;

	/**
	 * @param Backend $backend
	 */
	public function __construct( Backend $backend ) {
		$this->backend = $backend;
	}

	/**
	 * @return string
	 */
	public function getKeyForApi(): string {
		return 'ext-bluespiceextendedsearch-backend-connectivity';
	}

	/**
	 * @return string
	 */
	public function getValueForApi() {
		try {
			$client = $this->backend->getClient();

			if ( !$client->ping() ) {
				return 'OpenSearch unreachable';
			}

			return 'OK';
		} catch ( Throwable $e ) {
			return 'Exception: ' . $e->getMessage();
		}
	}
}
