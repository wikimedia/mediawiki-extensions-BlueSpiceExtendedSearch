<?php

namespace BS\ExtendedSearch\Source\Crawler;

use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\SpecialPage\SpecialPageFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class SpecialPage extends Base {
	/** @inheritDoc */
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateSpecialPage';

	/**
	 * @var SpecialPageFactory
	 */
	protected $specialPageFactory = null;

	/**
	 * @param ILoadBalancer $lb
	 * @param JobQueueGroup $jobQueueGroup
	 * @param SpecialPageFactory $specialPageFactory
	 * @param Config $sourceConfig
	 */
	public function __construct(
		ILoadBalancer $lb, JobQueueGroup $jobQueueGroup, SpecialPageFactory $specialPageFactory, Config $sourceConfig
	) {
		parent::__construct( $lb, $jobQueueGroup, $sourceConfig );
		$this->specialPageFactory = $specialPageFactory;
	}

	public function crawl() {
		$aCanonicalNames = $this->specialPageFactory->getNames();
		foreach ( $aCanonicalNames as $sCanonicalName ) {
			$specialPage = $this->specialPageFactory->getPage( $sCanonicalName );
			if ( !$specialPage ) {
				continue;
			}
			$this->addToJobQueue( $specialPage->getPageTitle() );
		}
	}
}
