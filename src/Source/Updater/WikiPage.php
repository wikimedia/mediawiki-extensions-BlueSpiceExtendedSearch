<?php

namespace BS\ExtendedSearch\Source\Updater;

class WikiPage extends Base {
	public function init( &$aHooks ) {
		$aHooks['PageContentSaveComplete'][] = array( $this, 'onPageContentSaveComplete' );
		$aHooks['ArticleDeleteComplete'][] = array( $this, 'onArticleDeleteComplete' );
		$aHooks['ArticleUndelete'][] = array( $this, 'onArticleUndelete' );
		$aHooks['TitleMoveComplete'][] = array( $this, 'onTitleMoveComplete' );

		parent::init( $aHooks );
	}

	/**
	 * Update index on article change.
	 *
	 * @param \WikiPage $wikiPage
	 * @param \User $user
	 * @param \Content $content
	 * @param string $summary
	 * @param boolean $isMinor
	 * @param boolean $isWatch
	 * @param int $section
	 * @param int $flags
	 * @param \Revision $revision
	 * @param \Status $status
	 * @param int $baseRevId
	 *
	 * @return boolean
	 */
	public function onPageContentSaveComplete( \WikiPage $wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $wikiPage->getTitle() )
		);
		return true;
	}

	/**
	 * Delete search index entry on article deletion
	 * @param \WikiPage $article
	 * @param \User $user
	 * @param type $reason
	 * @param type $id
	 * @param \Content $content
	 * @param \LogEntry $logEntry
	 * @return boolean
	 */
	public function onArticleDeleteComplete( &$article, \User &$user, $reason, $id, \Content $content = null, \LogEntry $logEntry ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $article->getTitle() )
		);
		return true;
	}

	/**
	 * Update index on article undelete
	 * @param Title $title
	 * @param boolean $create
	 * @param string $comment
	 * @param int $oldPageId
	 * @return boolean
	 */
	public function onArticleUndelete( \Title $title, $create, $comment, $oldPageId ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateWikiPage( $title )
		);
		return true;
	}

	/**
	 * Update search index when an article is moved.
	 * @param \Title $title
	 * @param \Title $newtitle
	 * @param \User $user
	 * @param int $oldid
	 * @param int $newid
	 * @param string $reason
	 * @param \Revision $revision
	 * @return boolean
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