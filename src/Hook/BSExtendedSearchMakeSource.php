<?php

namespace BS\ExtendedSearch\Hook;

use BlueSpice\Hook;

abstract class BSExtendedSearchMakeSource extends Hook {
	/**
	 * @var \BS\ExtendedSearch\Backend
	 */
	protected $backend;

	/**
	 * @var string
	 */
	protected $sourceKey;

	/**
	 * @var \BS\ExtendedSearch\Source\DecoratorBase
	 */
	protected $decoratedSource;

	/**
	 * @param \BS\ExtendedSearch\Backend$backend
	 * @param string $sourceKey
	 * @param \BS\ExtendedSearch\Source\DecoratorBase $decoratedSource
	 * @return mixed
	 */
	public static function callback( $backend, $sourceKey, &$decoratedSource ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$backend,
			$sourceKey,
			$decoratedSource
		);
		return $hookHandler->process();
	}

	/**
	 * BSExtendedSearchMakeSource constructor.
	 * @param \IContextSource $context
	 * @param \IConfig $config
	 * @param \BS\ExtendedSearch\Backend$backend
	 * @param string $sourceKey
	 * @param \BS\ExtendedSearch\Source\DecoratorBase $decoratedSource
	 */
	public function __construct( $context, $config, $backend, $sourceKey, &$decoratedSource ) {
		parent::__construct( $context, $config );

		$this->backend = $backend;
		$this->sourceKey = $sourceKey;
		$this->decoratedSource &= $decoratedSource;
	}
}