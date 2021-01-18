<?php
namespace BS\ExtendedSearch\Source\Updater;

use MediaWiki\HookContainer\HookContainer;

class SpecialPage extends Base {
	/**
	 *
	 * @param HookContainer $hookContainer
	 */
	public function init( $hookContainer ) {
		$hookContainer->register(
			'LoadExtensionSchemaUpdates', [ $this, 'onLoadExtensionSchemaUpdates' ]
		);

		parent::init( $hookContainer );
	}

	/**
	 * Update index if new extensions are being installed
	 * @param \DatabaseUpdater $updater Updater
	 * @return bool Always true
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$oCrawler = new \BS\ExtendedSearch\Source\Crawler\SpecialPage();
		$oCrawler->crawl();
		return true;
	}
}
