<?php

namespace BS\ExtendedSearch\Hook;

use BlueSpice\Hook;
use BS\ExtendedSearch\Source\Job\UpdateBase;
use Config;
use IContextSource;

abstract class BSExtendedSearchIndexDocumentSkip extends Hook {
	/** @var UpdateBase */
	protected $updateJob;

	/** @var bool */
	protected $skip;

	/**
	 * @param UpdateBase $updateJob
	 * @param bool &$skip
	 * @return bool
	 */
	public static function callback( $updateJob, &$skip ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$updateJob,
			$skip
		);
		return $hookHandler->process();
	}

	/**
	 * BSExtendedSearchMakeSource constructor.
	 * @param IContextSource $context
	 * @param Config $config
	 * @param UpdateBase $updateJob
	 * @param bool &$skip
	 */
	public function __construct( $context, $config, $updateJob, &$skip ) {
		parent::__construct( $context, $config );

		$this->updateJob = $updateJob;
		$this->skip =& $skip;
	}
}
