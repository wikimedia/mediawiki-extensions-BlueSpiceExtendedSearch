<?php

namespace BS\ExtendedSearch\MediaWiki\Rest;

use BS\ExtendedSearch\SearchTracker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use RequestContext;
use User;

class RecentlyFoundHandler extends SimpleHandler {
	/**
	 * @var SearchTracker
	 */
	private $tracker;

	/**
	 * @var LinkRenderer
	 */
	private $linkRenderer;

	/**
	 * @param SearchTracker $tracker
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( SearchTracker $tracker, LinkRenderer $linkRenderer ) {
		$this->tracker = $tracker;
		$this->linkRenderer = $linkRenderer;
	}

	public function execute() {
		$titles = $this->getRecentTitles( RequestContext::getMain()->getUser() );
		return $this->formatAndReturn( $titles );
	}

	/**
	 * @param User $user
	 *
	 * @return array|\Title[]
	 */
	private function getRecentTitles( User $user ): array {
		if ( !$user->isRegistered() ) {
			return $this->tracker->getForAnonymous();
		}
		return $this->tracker->getForUser( $user );
	}

	/**
	 * @param array $title
	 *
	 * @return void
	 */
	protected function formatAndReturn( array $title ): Response {
		$suggestions = [];
		foreach ( $title as $t ) {
			$suggestions[] = [
				"_id" => '',
				"type" => $t->isSpecialPage() ? 'specialpage' : 'wikipage',
				"score" => 1,
				"rank" => 'normal',
				'page_anchor' => $this->linkRenderer->makeLink( $t ),
			];
		}

		return $this->getResponseFactory()->createJson( [ 'suggestions' => $suggestions ] );
	}
}
