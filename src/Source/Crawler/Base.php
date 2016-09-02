<?php

namespace BS\ExtendedSearch\Source\Crawler;

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
		//Needs to be implemented by sublasses; but not abstract as this may server as a stub
	}

	/**
	 *
	 * @param \Title $oTitle
	 * @param array $aParams
	 */
	protected function addToJobQueue( $oTitle, $aParams = [] ) {
		if( empty( $this->sJobClass ) ) {
			return;
		}

		\JobQueueGroup::singleton()->push(
			new $this->sJobClass( $oTitle, $aParams )
		);
	}

	/**
	 *
	 * @return int
	 */
	public function getNumberOfPendingJobs() {
		if( empty( $this->sJobClass ) ) {
			return -1;
		}

		$oDummyJob = new $this->sJobClass( \Title::newMainPage() );
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->selectRow(
			'job',
			'COUNT(*) AS count',
			[ 'jobCmd' => $oDummyJob->getType() ]
		);

		return $res->count;
	}
}