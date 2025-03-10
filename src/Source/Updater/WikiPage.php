<?php

namespace BS\ExtendedSearch\Source\Updater;

use BS\ExtendedSearch\Source\Job\UpdateWikiPage;
use JobQueueGroup;
use ManualLogEntry;
use MediaWiki\Extension\ContentStabilization\StablePoint;
use MediaWiki\Hook\AfterImportPageHook;
use MediaWiki\Hook\PageMoveCompletingHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\Hook\PageUndeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;

class WikiPage extends Base implements
	PageSaveCompleteHook,
	PageDeleteCompleteHook,
	PageUndeleteCompleteHook,
	PageMoveCompletingHook,
	AfterImportPageHook
{

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 *
	 * @param MediaWikiServices $services
	 */
	public function init( MediaWikiServices $services ): void {
		$services->getHookContainer()->register(
			'PageSaveComplete', [ $this, 'onPageSaveComplete' ]
		);
		$services->getHookContainer()->register(
			'PageDeleteComplete', [ $this, 'onPageDeleteComplete' ]
		);
		$services->getHookContainer()->register(
			'PageUndeleteComplete', [ $this, 'onPageUndeleteComplete' ]
		);
		$services->getHookContainer()->register(
			'PageMoveCompleting', [ $this, 'onPageMoveCompleting' ]
		);
		$services->getHookContainer()->register(
			'AfterImportPage', [ $this, 'onAfterImportPage' ]
		);
		$services->getHookContainer()->register(
			'ContentStabilizationStablePointAdded', [ $this, 'onContentStabilizationStablePointAdded' ]
		);
		$services->getHookContainer()->register(
			'ContentStabilizationStablePointRemoved', [ $this, 'onContentStabilizationStablePointRemoved' ]
		);
		$this->jobQueueGroup = $services->getJobQueueGroup();

		parent::init( $services );
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		$this->jobQueueGroup->push( new UpdateWikiPage( $wikiPage->getTitle() ) );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageDeleteComplete(
		ProperPageIdentity $page, Authority $deleter, string $reason, int $pageID,
		RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount
	) {
		$title = Title::castFromPageIdentity( $page );
		$this->jobQueueGroup->push( new UpdateWikiPage( $title ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onPageUndeleteComplete(
		ProperPageIdentity $page,
		Authority $restorer,
		string $reason,
		RevisionRecord $restoredRev,
		ManualLogEntry $logEntry,
		int $restoredRevisionCount,
		bool $created,
		array $restoredPageIds
	): void {
		$title = Title::newFromPageIdentity( $page );
		$this->jobQueueGroup->push( new UpdateWikiPage( $title ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onAfterImportPage( $title, $foreignTitle, $revCount, $sRevCount, $pageInfo ) {
		$this->jobQueueGroup->push( new UpdateWikiPage( $title ) );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onContentStabilizationStablePointAdded( StablePoint $stablePoint ): void {
		$title = Title::castFromPageIdentity( $stablePoint->getPage() );
		$this->jobQueueGroup->push( new UpdateWikiPage( $title ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onContentStabilizationStablePointRemoved( StablePoint $removedPoint, Authority $remover ): void {
		$title = Title::castFromPageIdentity( $removedPoint->getPage() );
		$this->jobQueueGroup->push( new UpdateWikiPage( $title ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onPageMoveCompleting( $old, $new, $user, $pageid, $redirid, $reason, $revision ) {
		$jobs = [
			new UpdateWikiPage(
				Title::newFromLinkTarget( $old )
			),
			new UpdateWikiPage(
				Title::newFromLinkTarget( $new )
			),
		];
		$this->jobQueueGroup->push( $jobs );
	}
}
