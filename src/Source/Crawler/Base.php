<?php

namespace BS\ExtendedSearch\Source\Crawler;

use MediaWiki\MediaWikiServices;

class Base {

	protected $sJobClass = '';

	/**
	 *
	 * @var \Config
	 */
	protected $oConfig = null;

	/**
	 *
	 * @param \Config $oConfig
	 */
	public function __construct( $oConfig ) {
		$this->oConfig = $oConfig;
	}

	public function crawl() {
		//Needs to be implemented by sublasses; but not abstract as this may serve as a stub
	}

	/**
	 *
	 * @param \Title $oTitle
	 * @param array $aParams
	 * @return \Job
	 */
	protected function addToJobQueue( $oTitle, $aParams = [] ) {
		if( empty( $this->sJobClass ) ) {
			return;
		}

		$oJob = new $this->sJobClass( $oTitle, $aParams );
		\JobQueueGroup::singleton()->push( $oJob );
		return $oJob;
	}

	/**
	 *
	 * @return int
	 */
	public function getNumberOfPendingJobs() {
		if( empty( $this->sJobClass ) ) {
			return -1;
		}

		$oDummyJob = new $this->sJobClass( \Title::newMainPage(), [] );
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->selectRow(
			'job',
			'COUNT(*) AS count',
			[ 'job_cmd' => $oDummyJob->getType() ]
		);

		return $res->count;
	}

	/**
	 *
	 * @return boolean
	 */
	public function clearPendingJobs() {
		if( empty( $this->sJobClass ) ) {
			return false;
		}

		$oDummyJob = new $this->sJobClass( \Title::newMainPage(), [] );
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->delete(
			'job',
			[ 'job_cmd' => $oDummyJob->getType() ]
		);

		return $res !== false;
	}
}