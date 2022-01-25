<?php

namespace BS\ExtendedSearch\Source\Updater;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;

class Base {

	/** @var string */
	protected $sUpdateJobClass = '';

	/**
	 *
	 * @param \BS\ExtendedSearch\Source\Base $oSource
	 */
	public function __construct( $oSource ) {
		// TODO: Proceed here
	}

	/**
	 *
	 * @param HookContainer $hookContainer
	 */
	public function init( $hookContainer ) {
		$hookContainer->register(
			'BSExtendedSearchTriggerUpdate', [ $this, 'onBSExtendedSearchTriggerUpdate' ]
		);
	}

	/**
	 *
	 * @param \Title $oTitle
	 * @param array $aParams
	 * @return void
	 */
	public function addUpdateJob( $oTitle, $aParams = [] ) {
		$oJob = $this->makeJob( $oTitle, $aParams );
		if ( $oJob instanceof \Job === false ) {
			return;
		}

		MediaWikiServices::getInstance()->getJobQueueGroup()->push( $oJob );
	}

	/**
	 * @param \Title $oTitle
	 * @param array $aParams
	 * @return \BS\ExtendedSearch\Source\Updater\Base|null
	 */
	public function makeJob( $oTitle, $aParams = [] ) {
		if ( !is_subclass_of( $this->sUpdateJobClass, '\Job' ) ) {
			$sCurrentClassName = get_class( $this );
			wfDebugLog( 'BSExtendedSearch', "Updater '$sCurrentClassName has no valid JobClass" );
			return null;
		}

		return new $this->sUpdateJobClass( $oTitle, $aParams );
	}

	/**
	 *
	 * @param string $sBackendKey
	 * @param string $sSourceKey
	 * @param \Title $oTitle
	 * @param array $aParams
	 * @return bool
	 */
	public function onBSExtendedSearchTriggerUpdate( $sBackendKey, $sSourceKey, $oTitle, $aParams ) {
		$this->addUpdateJob( $oTitle, $aParams );
		return true;
	}
}
