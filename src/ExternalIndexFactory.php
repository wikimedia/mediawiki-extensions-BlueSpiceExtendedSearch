<?php

namespace BS\ExtendedSearch;

use BlueSpice\ExtensionAttributeBasedRegistry;
use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

class ExternalIndexFactory {
	/**
	 *
	 * @var Config
	 */
	protected $config = null;

	/**
	 *
	 * @var ExtensionAttributeBasedRegistry
	 */
	protected $registry = null;

	/**
	 *
	 * @param Config $config
	 * @param ExtensionAttributeBasedRegistry $registry
	 */
	public function __construct( Config $config, ExtensionAttributeBasedRegistry $registry ) {
		$this->config = $config;
		$this->registry = $registry;
	}

	/**
	 *
	 * @return array
	 */
	public function getTypes() {
		return $this->registry->getAllKeys();
	}

	/**
	 *
	 * @param string $type
	 * @param array $document
	 * @return IExternalIndex|null
	 */
	public function getExternalIndex( $type, $document ) {
		$callback = $this->registry->getValue( $type, false );
		if ( !$callback || !is_callable( $callback ) ) {
			return null;
		}
		return call_user_func_array( $callback, [
			MediaWikiServices::getInstance(),
			$this->config,
			$document
		] );
	}

}
