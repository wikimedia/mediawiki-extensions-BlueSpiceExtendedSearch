<?php

namespace BS\ExtendedSearch\Source\Updater;

use MediaWiki\Revision\RevisionStoreRecord;
use MediaWiki\Storage\EditResult;
use User;
use WikiPage as MWWikiPage;

class WikiPage extends Base {
	/**
	 *
	 * @param array &$aHooks
	 */
	public function init( &$aHooks ) {
		$aHooks['PageSaveComplete'][] = [ $this, 'onPageSaveComplete' ];
		$aHooks['ArticleDeleteComplete'][] = [ $this, 'onArticleDeleteComplete' ];
		$aHooks['ArticleUndelete'][] = [ $this, 'onArticleUndelete' ];
		$aHooks['TitleMoveComplete'][] = [ $this, 'onTitleMoveComplete' ];

		parent::init( $aHooks );
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
		\JobQueueGroup::singleton()->push(
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
		\JobQueueGroup::singleton()->push(
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
	public function onArticleUndelete( \Title $title, $create, $comment, $oldPageId ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $title )
		);
		return true;
	}

	/**
	 * Update search index when an article is moved.
	 * @param \Title &$title
	 * @param \Title &$newtitle
	 * @param \User &$user
	 * @param int $oldid
	 * @param int $newid
	 * @param string $reason
	 * @param \Revision $revision
	 * @return bool
	 */
	public function onTitleMoveComplete( \Title &$title, \Title &$newtitle, \User &$user, $oldid, $newid, $reason, \Revision $revision ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $title )
		);
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $newtitle )
		);
		return true;
	}
}
