<?php

namespace BS\ExtendedSearch\MediaWiki\Rest;

use BS\ExtendedSearch\SearchTracker;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

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
	 * @return array|Title[]
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
				'page_anchor' => $this->getTraceablePageAnchor( $t )
			];
		}

		return $this->getResponseFactory()->createJson( [ 'suggestions' => $suggestions ] );
	}

	/**
	 * @param Title $title
	 *
	 * @return string
	 */
	protected function getTraceablePageAnchor( Title $title ): string {
		$data = [
			'dbkey' => $title->getDBkey(),
			'namespace' => $title->getNamespace(),
			'url' => $title->getFullURL()
		];

		return Html::element( 'a', [
			'href' => $title->getLocalURL(),
			'class' => 'bs-traceable-link bs-recently-found-suggestion',
			'data-bs-traceable-page' => json_encode( $data ),
			'data-title' => $title->getPrefixedText()
		], $title->getPrefixedText() );
	}
}
