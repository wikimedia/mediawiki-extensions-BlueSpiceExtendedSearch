<?php

namespace BS\ExtendedSearch\Source\Updater;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionStoreRecord;
use MediaWiki\Storage\EditResult;
use Title;
use User;
use WikiPage as MWWikiPage;

class WikiPage extends Base {
	/**
	 *
	 * @param HookContainer $hookContainer
	 */
	public function init( $hookContainer ) {
		$hookContainer->register(
			'PageSaveComplete', [ $this, 'onPageSaveComplete' ]
		);
		$hookContainer->register(
			'ArticleDeleteComplete', [ $this, 'onArticleDeleteComplete' ]
		);
		$hookContainer->register(
			'ArticleUndelete', [ $this, 'onArticleUndelete' ]
		);
		$hookContainer->register(
			'PageMoveComplete', [ $this, 'onTitleMoveComplete' ]
		);
		$hookContainer->register(
			'AfterImportPage', [ $this, 'onAfterImportPage' ]
		);

		parent::init( $hookContainer );
	}

	/**
	 * Update index on article change.
	 *
	 * @param MWWikiPage $wikiPage
	 * @param User $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionStoreRecord $revisionRecord
	 * @param EditResult $editResult
	 * @return bool
	 */
	public function onPageSaveComplete( MWWikiPage $wikiPage, User $user, string $summary,
		int $flags, RevisionStoreRecord $revisionRecord, EditResult $editResult ) {
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $wikiPage->getTitle() )
		);
		return true;
	}

	/**
	 * Delete search index entry on article deletion
	 * @param \WikiPage &$article
	 * @param \User &$user
	 * @param string $reason
	 * @param int $id
	 * @param \Content|null $content
	 * @param \LogEntry $logEntry
	 * @return bool
	 */
	public function onArticleDeleteComplete( &$article, \User &$user, $reason, $id, ?\Content $content, \LogEntry $logEntry ) {
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $article->getTitle() )
		);
		return true;
	}

	/**
	 * Update index on article undelete
	 * @param Title $title
	 * @param bool $create
	 * @param string $comment
	 * @param int $oldPageId
	 * @return bool
	 */
	public function onArticleUndelete( Title $title, $create, $comment, $oldPageId ) {
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $title )
		);
		return true;
	}

	/**
	 * Update search index when an article is moved.
	 * @param LinkTarget $title Old title of the moved article.
	 * @param LinkTarget $newtitle New title of the moved article.
	 * @param UserIdentity $user User that moved the article.
	 * @return bool
	 */
	public function onTitleMoveComplete( $title, $newtitle, $user ) {
		$jobs = [
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage(
				Title::newFromLinkTarget( $title )
			),
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage(
				Title::newFromLinkTarget( $newtitle )
			),
		];
		MediaWikiServices::getInstance()->getJobQueueGroup()->push( $jobs );
		return true;
	}

	/**
	 *
	 * @param Title $title
	 * @param string $origTitle
	 * @param int $revCount
	 * @param int $sRevCount
	 * @param array $pageInfo
	 * @return bool
	 */
	public function onAfterImportPage( $title, $origTitle, $revCount, $sRevCount, $pageInfo ) {
		if ( empty( $sRevCount ) ) {
			return true;
		}
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $title )
		);
		return true;
	}
}
