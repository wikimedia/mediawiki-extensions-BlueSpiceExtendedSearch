<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\Source\Formatter\FileFormatter;

class ExternalFileFormatter extends FileFormatter {

	/**
	 * Just show icon associated with the extension.
	 * Browser won't show image on filesystem (file:///),
	 * it may work if URL prefix is set to a webserver
	 *
	 * @param array $result
	 */
	protected function getImage( $result ) {
		$extension = $result['extension'];

		//Is there a centralized place to get file icons, so
		//that those do not have to come with this extension?
		$fileIcons = \ExtensionRegistry::getInstance()
			->getAttribute( 'BlueSpiceExtendedSearchIcons' );

		$scriptPath = $this->getContext()->getConfig()->get( 'ScriptPath' );
		if( isset( $fileIcons[$extension] ) ) {
			return $scriptPath . $fileIcons[$extension];
		}
		return $scriptPath . $fileIcons['default'];
	}
}
