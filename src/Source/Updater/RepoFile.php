<?php

namespace BS\ExtendedSearch\Source\Updater;

use BS\ExtendedSearch\Source\Job\UpdateTitleBase;

class RepoFile extends Base {
	public function init( &$aHooks ) {
		$aHooks['FileUpload'][] = array( $this, 'onFileUpload' );
		$aHooks['FileDeleteComplete'][] = array( $this, 'onFileDeleteComplete' );
		$aHooks['FileUndeleteComplete'][] = array( $this, 'onFileUndeleteComplete' );
		$aHooks['TitleMove'][] = array( $this, 'onTitleMove' );
		$aHooks['TitleMoveComplete'][] = array( $this, 'onTitleMoveComplete' );

		parent::init( $aHooks );
	}

	/**
	 * Update index on file upload
	 * @param \File $oFile MediaWiki file object of uploaded file
	 * @param bool $bReupload indicates if file was uploaded before
	 * @param bool $bHasDescription indicates if a description page existed before
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onFileUpload( $oFile, $bReupload = false, $bHasDescription = false ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateRepoFile( $oFile->getTitle() )
		);
		return true;
	}

	/**
	 * Delete file from index when file is deleted
	 * @param File $oFile MediaWiki file object of deleted file
	 * @param File $oOldimage the name of the old file
	 * @param Article $oArticle reference to the article if all revisions are deleted
	 * @param User $oUser user who performed the deletion
	 * @param string $sReason reason
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onFileDeleteComplete( $oFile, $oOldimage, $oArticle, $oUser, $sReason ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateRepoFile( $oFile->getTitle(), [ 'file' => $oFile ] )
		);
		return true;
	}

	/**
	 * Update index when file is undeleted
	 * @param Title $oTitle MediaWiki title object of undeleted file
	 * @param array $aFileVersions array of undeleted versions
	 * @param User $oUser user who performed the undeletion
	 * @param string $sReason reason
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onFileUndeleteComplete( $oTitle, $aFileVersions, $oUser, $sReason ) {
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateRepoFile( $oTitle )
		);
		return true;
	}

	/**
	 * Holds instance of file before its moved
	 *
	 * @var \File
	 */
	protected $titleMoveOrigFile;

	public function onTitleMove( $title, $newtitle, $user )  {
		if( $title->getNamespace() !== NS_FILE ) {
			return true;
		}

		$file = \RepoGroup::singleton()->findFile( $title );
		$this->titleMoveOrigFile = $file;
	}

	/**
	 * Update search index when a file is moved.
	 * @param Title $oTitle Old title of the moved article.
	 * @param Title $oNewtitle New title of the moved article.
	 * @param User $oUser User that moved the article.
	 * @param int $iOldID ID of the page that has been moved.
	 * @param int $iNewID ID of the newly created redirect.
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onTitleMoveComplete( &$oTitle, &$oNewtitle, $oUser, $iOldID, $iNewID ) {
		if( $oTitle->getNamespace() !== NS_FILE ) {
			return true;
		}

		$oldFile = new \LocalFile( $oTitle, \RepoGroup::singleton()->getLocalRepo() );
		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateRepoFile(
				$oTitle,
				[
					'file' => $this->titleMoveOrigFile,
					'action' => \BS\ExtendedSearch\Source\Job\UpdateRepoFile::ACTION_DELETE
				]
			)
		);

		\JobQueueGroup::singleton()->push(
			new \BS\ExtendedSearch\Source\Job\UpdateRepoFile( $oNewtitle )
		);
		return true;
	}
}
