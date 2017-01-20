<?php
namespace BS\ExtendedSearch\Source\Updater;

class SpecialPage extends Base {
	public function init( &$aHooks ) {
		$aHooks['LoadExtensionSchemaUpdates'][] = array( $this, 'onLoadExtensionSchemaUpdates' );

		parent::init( $aHooks );
	}

	/**
	 * Update index if new extensions are being installed
	 * @param object §updater Updater
	 * @return boolean Always true
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$oCrawler = new \BS\ExtendedSearch\Source\Crawler\SpecialPage();
		$oCrawler->crawl();
		return true;
	}
}