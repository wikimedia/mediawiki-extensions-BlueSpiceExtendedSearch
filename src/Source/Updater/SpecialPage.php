<?php
namespace BS\ExtendedSearch\Source\Updater;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\MediaWikiServices;

class SpecialPage extends Base {
	/**
	 *
	 * @param MediaWikiServices $services
	 */
	public function init( MediaWikiServices $services ): void {
		$services->getHookContainer()->register(
			'LoadExtensionSchemaUpdates', [ $this, 'onLoadExtensionSchemaUpdates' ]
		);

		parent::init( $services );
	}

	/**
	 * Update index if new extensions are being installed
	 * @param DatabaseUpdater $updater Updater
	 * @return bool Always true
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$oCrawler = $this->source->getCrawler();
		$oCrawler->crawl();
		return true;
	}
}
