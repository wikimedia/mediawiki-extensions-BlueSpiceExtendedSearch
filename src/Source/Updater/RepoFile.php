<?php

namespace BS\ExtendedSearch\Source\Updater;

use Article;
use BS\ExtendedSearch\Source\Job\UpdateRepoFile;
use File;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Title;
use User;

class RepoFile extends Base {

	/**
	 *
	 * @param HookContainer $hookContainer
	 */
	public function init( $hookContainer ) {
		$hookContainer->register(
			'FileUpload', [ $this, 'onFileUpload' ]
		);
		$hookContainer->register(
			'FileDeleteComplete', [ $this, 'onFileDeleteComplete' ]
		);
		$hookContainer->register(
			'FileUndeleteComplete', [ $this, 'onFileUndeleteComplete' ]
		);
		$hookContainer->register(
			'TitleMove', [ $this, 'onTitleMove' ]
		);
		$hookContainer->register(
			'PageMoveComplete', [ $this, 'onTitleMoveComplete' ]
		);
		$hookContainer->register(
			'WebDAVPublishToWikiDone', [ $this, 'onWebDAVPublishToWikiDone' ]
		);

		parent::init( $hookContainer );
	}

	/**
	 * Update index on file upload
	 * @param \File $oFile MediaWiki file object of uploaded file
	 * @param bool $bReupload indicates if file was uploaded before
	 * @param bool $bHasDescription indicates if a description page existed before
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onFileUpload( $oFile, $bReupload = false, $bHasDescription = false ) {
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new UpdateRepoFile( $oFile->getTitle() )
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
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new UpdateRepoFile( $oFile->getTitle(), [
				'filedata' => $this->getFileData( $oFile )
			] )
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
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new UpdateRepoFile( $oTitle )
		);
		return true;
	}

	/**
	 * Holds instance of file before its moved
	 *
	 * @var \File
	 */
	protected $titleMoveOrigFile;

	/**
	 *
	 * @param Title $title
	 * @param Title $newtitle
	 * @param User $user
	 * @return bool
	 */
	public function onTitleMove( $title, $newtitle, $user ) {
		if ( $title->getNamespace() !== NS_FILE ) {
			return true;
		}

		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		$this->titleMoveOrigFile = $file;
		return true;
	}

	/**
	 * Update search index when a file is moved.
	 * @param LinkTarget $oTitle Old title of the moved article.
	 * @param LinkTarget $oNewtitle New title of the moved article.
	 * @param UserIdentity $oUser User that moved the article.
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onTitleMoveComplete( $oTitle, $oNewtitle, $oUser ) {
		if ( $oTitle->getNamespace() !== NS_FILE ) {
			return true;
		}

		$jobs = [
			new \BS\ExtendedSearch\Source\Job\UpdateRepoFile(
				Title::newFromLinkTarget( $oTitle ),
				[
					'filedata' => $this->getFileData( $this->titleMoveOrigFile ),
					'action' => UpdateRepoFile::ACTION_DELETE
				]
			),
			new UpdateRepoFile( Title::newFromLinkTarget( $oNewtitle ) ),
		];
		MediaWikiServices::getInstance()->getJobQueueGroup()->push( $jobs );
		return true;
	}

	/**
	 * @param File $repoFile
	 * @param string $sourceFilePath
	 */
	public function onWebDAVPublishToWikiDone( $repoFile, $sourceFilePath ) {
		if ( $repoFile->getTitle() instanceof Title ) {
			MediaWikiServices::getInstance()->getJobQueueGroup()->push(
				new UpdateRepoFile( $repoFile->getTitle() )
			);
		}
	}

	/**
	 * @param File $oFile
	 * @return array
	 */
	protected function getFileData( File $oFile ) {
		$fileBackend = $oFile->getRepo()->getBackend();
		return [
			'canonicalUrl' => $oFile->getCanonicalUrl(),
			'fsFile' => $fileBackend->getLocalReference( [
				'src' => $oFile->getPath()
			] )
		];
	}
}
