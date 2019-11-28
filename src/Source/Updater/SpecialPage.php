<?php
namespace BS\ExtendedSearch\Source\Updater;

class SpecialPage extends Base {
	/**
	 *
	 * @param array &$aHooks
	 */
	public function init( &$aHooks ) {
		$aHooks['LoadExtensionSchemaUpdates'][] = [ $this, 'onLoadExtensionSchemaUpdates' ];

		parent::init( $aHooks );
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
