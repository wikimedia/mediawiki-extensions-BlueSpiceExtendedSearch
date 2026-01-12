<?php

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\Source\Updater\ExternalFile;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\ProcessManager\ManagedProcess;
use MWStake\MediaWiki\Component\WikiCron\WikiCronManager;

class ExternalFileUpdaterCron {

	/**
	 * @return void
	 */
	public static function register(): void {
		if ( defined( 'MW_PHPUNIT_TEST' ) || defined( 'MW_QUIBBLE_CI' ) ) {
			return;
		}

		/** @var WikiCronManager $cronManager */
		$cronManager = MediaWikiServices::getInstance()->getService( 'MWStake.WikiCronManager' );

		// Interval: Daily at 01:00
		$cronManager->registerCron( 'bs-extendedsearch-update-external-files', '0 1 * * *', new ManagedProcess( [
			'update-external-files' => [
				'class' => ExternalFile::class,
				'services' => [
					'ConfigFactory',
					'DBLoadBalancer',
					'BSExtendedSearchBackend'
				],
			]
		] ) );
	}
}
