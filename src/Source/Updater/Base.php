<?php

namespace BS\ExtendedSearch\Source\Updater;

use BS\ExtendedSearch\ISearchSource;
use BS\ExtendedSearch\ISearchUpdater;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class Base implements ISearchUpdater {

	/** @var string */
	protected $sUpdateJobClass = '';
	/** @var ISearchSource */
	protected $source;

	/**
	 *
	 * @param ISearchSource $source
	 */
	public function __construct( ISearchSource $source ) {
		$this->source = $source;
	}

	/**
	 *
	 * @param MediaWikiServices $services
	 */
	public function init( MediaWikiServices $services ): void {
		$services->getHookContainer()->register(
			'BSExtendedSearchTriggerUpdate', [ $this, 'onBSExtendedSearchTriggerUpdate' ]
		);
	}

	/**
	 *
	 * @param Title $oTitle
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
	 * @param Title $oTitle
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
	 * @param Title $oTitle
	 * @param array $aParams
	 * @return bool
	 */
	public function onBSExtendedSearchTriggerUpdate( $sBackendKey, $sSourceKey, $oTitle, $aParams ) {
		$this->addUpdateJob( $oTitle, $aParams );
		return true;
	}
}
