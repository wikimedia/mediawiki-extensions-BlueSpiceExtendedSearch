<?php

namespace BS\ExtendedSearch;

use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

class ClientConfig {

	/**
	 * @return string
	 */
	public static function makeConfigJson(): string {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		return json_encode( [
			'useSubpagePills' => self::useSubpagePills( $config )
		] );
	}

	/**
	 * @param Config $config
	 * @return bool
	 */
	private static function useSubpagePills( Config $config ): bool {
		if ( !$config->get( 'ESAutoRecognizeSubpages' ) ) {
			return false;
		}
		if (
			$config->has( 'ESUseSubpagePillsAutocomplete' ) &&
			!$config->get( 'ESUseSubpagePillsAutocomplete' )
		) {
			return false;
		}

		return true;
	}
}
