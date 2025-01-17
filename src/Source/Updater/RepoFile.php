<?php

namespace BS\ExtendedSearch\Source\Updater;

use Article;
use BS\ExtendedSearch\Source\Job\UpdateRepoFile;
use File;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;

class RepoFile extends Base {

	/**
	 *
	 * @param MediaWikiServices $services
	 */
	public function init( MediaWikiServices $services ): void {
		parent::init( $services );

		$services->getHookContainer()->register(
			'FileUpload', [ $this, 'onFileUpload' ]
		);
		$services->getHookContainer()->register(
			'FileDeleteComplete', [ $this, 'onFileDeleteComplete' ]
		);
		$services->getHookContainer()->register(
			'FileUndeleteComplete', [ $this, 'onFileUndeleteComplete' ]
		);
		$services->getHookContainer()->register(
			'TitleMove', [ $this, 'onTitleMove' ]
		);
		$services->getHookContainer()->register(
			'PageMoveComplete', [ $this, 'onTitleMoveComplete' ]
		);
		$services->getHookContainer()->register(
			'WebDAVPublishToWikiDone', [ $this, 'onWebDAVPublishToWikiDone' ]
		);
	}

	/**
	 * Update index on file upload
	 * @param \File $oFile MediaWiki file object of uploaded file
	 * @param bool $bReupload indicates if file was uploaded before
	 * @param bool $bHasDescription indicates if a description page existed before
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public function onFileUpload( $oFile, $bReupload = false, $bHasDescription = false ) {
		if ( $this->shouldSkip( $oFile ) ) {
			return true;
		}
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
		// Do not create job if only an old revision was deleted
		if ( $oOldimage ) {
			return true;
		}

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
		$newFile = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $oTitle );
		if ( !$newFile || $this->shouldSkip( $newFile ) ) {
			return true;
		}
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new UpdateRepoFile( $oTitle )
		);
		return true;
	}

	/**
	 * Holds instance of file before its moved
	 *
	 * @var \File|null
	 */
	protected $titleMoveOrigFile = null;

	/**
	 *
	 * @param Title $title
	 * @param Title $newtitle
	 * @param User $user
	 * @return bool
	 */
	public function onTitleMove( $title, $newtitle, $user ) {
		$this->titleMoveOrigFile = null;
		if ( $title->getNamespace() !== NS_FILE ) {
			return true;
		}
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		$this->titleMoveOrigFile = $file;

		$newFile = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $newtitle );
		if ( !$newFile || $this->shouldSkip( $newFile ) ) {
			return true;
		}
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
		if ( $this->titleMoveOrigFile ) {
			// Remove old file
			MediaWikiServices::getInstance()->getJobQueueGroup()->push(
				new \BS\ExtendedSearch\Source\Job\UpdateRepoFile(
					Title::newFromLinkTarget( $oTitle ),
					[
						'filedata' => $this->getFileData( $this->titleMoveOrigFile ),
						'action' => UpdateRepoFile::ACTION_DELETE
					]
				)
			);
		}
		$newFile = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $oNewtitle );
		if ( !$newFile || $this->shouldSkip( $newFile ) ) {
			return true;
		}
		// Index new file
		MediaWikiServices::getInstance()->getJobQueueGroup()->push(
			new UpdateRepoFile( Title::newFromLinkTarget( $oNewtitle ) )
		);

		return true;
	}

	/**
	 * @param File $repoFile
	 * @param string $sourceFilePath
	 * @return bool
	 */
	public function onWebDAVPublishToWikiDone( $repoFile, $sourceFilePath ) {
		if ( $this->shouldSkip( $repoFile ) ) {
			return true;
		}
		if ( $repoFile->getTitle() instanceof Title ) {
			MediaWikiServices::getInstance()->getJobQueueGroup()->push(
				new UpdateRepoFile( $repoFile->getTitle() )
			);
		}
		return true;
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

	/**
	 *
	 * @param File $file
	 * @return bool
	 */
	protected function shouldSkip( $file ) {
		$extensionBlacklist = $this->source->getConfig()->has( 'extension_blacklist' ) ?
			$this->source->getConfig()->get( 'extension_blacklist' ) :
			[];
		$lcExt = strtolower( $file->getExtension() );
		if ( in_array( $lcExt, $extensionBlacklist ) ) {
			return true;
		}

		return $file->getSize() && $file->getSize() > $this->source->getConfig()->get( 'max_size' );
	}
}
