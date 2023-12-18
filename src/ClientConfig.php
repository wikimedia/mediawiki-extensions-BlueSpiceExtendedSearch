<?php

namespace BS\ExtendedSearch;

use Config;
use MediaWiki\MediaWikiServices;

class ClientConfig {

	/**
	 * @return array
	 */
	public static function makeConfigJson(): array {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		return [
			'useSubpagePillsAutocomplete' => self::useSubpagePills( $config )
		];
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
