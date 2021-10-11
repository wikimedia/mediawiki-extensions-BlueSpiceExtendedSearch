<?php

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\Source\Job\UpdateBase;
use Config;
use MediaWiki\MediaWikiServices;
use Status;

interface IExternalIndex {

	public const FIELD_INDEX_NAME = 'indexname';
	public const FIELD_BACKEND_KEY = 'backendkey';
	public const FIELD_SOURCE_KEY = 'sourcekey';

	/**
	 *
	 * @param string $action
	 * @return Status
	 */
	public function push( $action = UpdateBase::ACTION_UPDATE );

	/**
	 *
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param array $document
	 * @return IExternalIndex
	 */
	public static function factory(
		MediaWikiServices $services, Config $config, array $document
	);

	/**
	 * [ 'fieldName in elasic' => 'fieldName for external index' ]
	 * @return array
	 */
	public function getMapping();
}
