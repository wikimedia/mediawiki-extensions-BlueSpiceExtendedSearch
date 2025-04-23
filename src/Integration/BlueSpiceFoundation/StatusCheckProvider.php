<?php

namespace BS\ExtendedSearch\Integration\BlueSpiceFoundation;

use BlueSpice\InstanceStatus\IStatusProvider;
use BS\ExtendedSearch\Backend;
use Throwable;

class StatusCheckProvider implements IStatusProvider {

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
	public function getLabel(): string {
		return 'ext-bluespiceextendedsearch-backend-connectivity';
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
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

	/**
	 * @return string
	 */
	public function getIcon(): string {
		return 'check';
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return 100;
	}
}
