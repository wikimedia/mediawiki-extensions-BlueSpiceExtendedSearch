<?php

namespace BS\ExtendedSearch\Hook;

use BlueSpice\Hook;
use Config;
use File;
use IContextSource;

abstract class BSExtendedSearchRepoFileGetRepoFile extends Hook {
	/** @var File */
	protected $file;

	/**
	 * @param File &$file
	 * @return bool
	 */
	public static function callback( &$file ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$file
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param File &$file
	 */
	public function __construct( $context, $config, &$file ) {
		parent::__construct( $context, $config );

		$this->file =& $file;
	}
}
