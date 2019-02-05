<?php

namespace BS\ExtendedSearch;

use Status;
use Config;
use BlueSpice\Services;
use BS\ExtendedSearch\Source\Job\UpdateBase;

interface IExternalIndex {

	const FIELD_INDEX_NAME = 'indexname';
	const FIELD_BACKEND_KEY = 'backendkey';
	const FIELD_SOURCE_KEY = 'sourcekey';

	/**
	 *
	 * @param string $action
	 * @return Status
	 */
	public function push( $action = UpdateBase::ACTION_UPDATE );

	/**
	 *
	 * @param Services $services
	 * @param Config $config
	 * @param array $document
	 * @return IExternalIndex
	 */
	public static function factory(
		Services $services, Config $config, array $document
	);

	/**
	 * [ 'fieldName in elasic' => 'fieldName for external index' ]
	 * @return array
	 */
	public function getMapping();
}
