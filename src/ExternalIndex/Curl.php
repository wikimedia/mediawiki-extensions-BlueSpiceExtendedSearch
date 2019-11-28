<?php

namespace BS\ExtendedSearch\ExternalIndex;

use MWHttpRequest;

abstract class Curl extends \BS\ExtendedSearch\ExternalIndex {

	/**
	 *
	 * @param array $mappedFields
	 * @param string $action
	 * @return MWHttpRequest
	 */
	protected function doPush( array $mappedFields, $action ) {
		$data = array_merge_recursive(
			$this->makeOptions( $mappedFields ),
			[ 'postData' => $mappedFields ]
		);
		$status = $this->makeRequest( $data, $action )->execute();

		return $status;
	}

	/**
	 * @param array $data
	 * @param string $action
	 * @return MWHttpRequest
	 */
	protected function makeRequest( $data, $action ) {
		return $this->services->getHttpRequestFactory()->create(
			$this->makeUrl( $action ),
			$data,
			__METHOD__
		);
	}

	/**
	 * @param string $action
	 * @return string
	 */
	abstract protected function makeUrl( $action );

	/**
	 * @return array
	 */
	protected function makeOptions() {
		return [
			'timeout' => 120,
		];
	}
}
