<?php

namespace BS\ExtendedSearch\Hook\MWStakeRunJobsTriggerRegisterHandlers;

use BS\ExtendedSearch\Source\Updater\ExternalFile;

class RegisterExternalFileUpdater {
	/**
	 *
	 * @param array &$handlers
	 * @return bool
	 */
	public static function callback( &$handlers ) {
		$handlers['bs-extendedsearch-update-external-files'] = [
			'class' => ExternalFile::class,
			'services' => [
				'ConfigFactory', 'DBLoadBalancer', 'BSExtendedSearchBackend'
			]
		];

		return true;
	}
}
