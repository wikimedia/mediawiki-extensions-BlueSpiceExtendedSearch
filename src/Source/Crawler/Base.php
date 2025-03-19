<?php

namespace BS\ExtendedSearch\Source\Crawler;

use BS\ExtendedSearch\ISearchCrawler;
use Job;
use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\ILoadBalancer;

class Base implements ISearchCrawler {

	/** @var string */
	protected $sJobClass = '';

	/**
	 *
	 * @var Config
	 */
	protected $sourceConfig = null;

	/**
	 *
	 * @var ILoadBalancer
	 */
	protected $lb = null;

	/**
	 *
	 * @var JobQueueGroup
	 */
	protected $jobQueueGroup = null;

	/**
	 *
	 * @param ILoadBalancer $lb
	 * @param JobQueueGroup $jobQueueGroup
	 * @param Config $sourceConfig
	 */
	public function __construct( ILoadBalancer $lb, JobQueueGroup $jobQueueGroup, Config $sourceConfig ) {
		$this->sourceConfig = $sourceConfig;
		$this->lb = $lb;
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * @return void
	 */
	public function crawl() {
		// Needs to be implemented by sublasses; but not abstract as this may serve as a stub
	}

	/**
	 *
	 * @param Title $oTitle
	 * @param array $aParams
	 * @return Job|null
	 */
	protected function addToJobQueue( $oTitle, $aParams = [] ) {
		if ( empty( $this->sJobClass ) ) {
			return null;
		}

		$oJob = new $this->sJobClass( $oTitle, $aParams );
		$this->jobQueueGroup->push( $oJob );
		return $oJob;
	}

	/**
	 *
	 * @return int
	 */
	public function getNumberOfPendingJobs(): int {
		if ( empty( $this->sJobClass ) ) {
			return -1;
		}

		$oDummyJob = new $this->sJobClass( Title::newMainPage(), [] );
		$dbr = $this->lb->getConnection( DB_REPLICA );
		$res = $dbr->selectRow(
			'job',
			'COUNT(*) AS count',
			[ 'job_cmd' => $oDummyJob->getType() ],
			__METHOD__
		);

		return $res->count;
	}

	/**
	 *
	 * @return bool
	 */
	public function clearPendingJobs(): bool {
		if ( empty( $this->sJobClass ) ) {
			return false;
		}

		$oDummyJob = new $this->sJobClass( Title::newMainPage(), [] );
		$dbw = $this->lb->getConnection( DB_PRIMARY );
		$res = $dbw->delete(
			'job',
			[ 'job_cmd' => $oDummyJob->getType() ],
			__METHOD__
		);

		return $res !== false;
	}
}
