<?php

namespace BS\ExtendedSearch\Hook;

use BlueSpice\Hook;
use Config;
use IContextSource;
use MediaWiki\Revision\RevisionRecord;
use Title;

abstract class BSExtendedSearchWikipageFetchRevision extends Hook {
	/** @var Title */
	protected $title;
	/** @var RevisionRecord */
	protected $revision;

	/**
	 * @param Title $title
	 * @param RevisionRecord &$revision
	 * @return bool
	 */
	public static function callback( $title, &$revision ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$title,
			$revision
		);
		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param Title $title
	 * @param RevisionRecord &$revision
	 */
	public function __construct( $context, $config, $title, &$revision ) {
		parent::__construct( $context, $config );

		$this->title = $title;
		$this->revision =& $revision;
	}
}
