<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use RepoGroup;
use SplFileInfo;
use Wikimedia\Mime\MimeAnalyzer;

class File extends Base {

	/** @var MimeAnalyzer */
	protected $mimeAnalyzer;

	/** @var RepoGroup */
	protected $repoGroup;

	/**
	 * @param MimeAnalyzer $mimeAnalyzer
	 */
	public function __construct( MimeAnalyzer $mimeAnalyzer ) {
		$this->mimeAnalyzer = $mimeAnalyzer;
		$this->repoGroup = MediaWikiServices::getInstance()->getRepoGroup();
	}

	/**
	 * @inheritDoc
	 */
	public function getDocumentData( $sUri, string $documentId, $oFile ): array {
		$contents = file_get_contents(
			$oFile->getPathname()
		);
		$contents = base64_encode( $contents );

		$aDC = parent::getDocumentData( $sUri, $documentId, $oFile );
		$name = $this->removeArchiveName( $oFile->getBasename() );

		return array_merge( $aDC, [
			'basename' => $name,
			'basename_exact' => $name,
			'extension' => $oFile->getExtension(),
			'mime_type' => $this->mimeAnalyzer->guessMimeType( $oFile->getPathname() ),
			'mtime' => $oFile->getMTime(),
			'ctime' => $this->getCreationTimestamp( $oFile ),
			'size' => $oFile->getSize(),
			'source_file_path' => $oFile->getPathname(),
			'the_file' => $contents
		] );
	}

	/**
	 * Make sure that the archive name for local files is stripped
	 *
	 * @param string $name
	 * @return string
	 */
	private function removeArchiveName( $name ) {
		$bits = explode( '!', $name );
		return array_pop( $bits );
	}

	/**
	 * Resolve the semantic creation timestamp for a file.
	 *
	 * For MediaWiki-managed files, returns the original upload date
	 * from the earliest archived version rather than the file's ctime,
	 * to match what users see on the file description page.
	 * Falls back to the local file's ctime/mtime.
	 *
	 * @param SplFileInfo $file
	 * @return int
	 */
	private function getCreationTimestamp( SplFileInfo $file ) {
		$title = Title::makeTitle( NS_FILE, $file->getBasename() );
		$repoFile = $this->repoGroup->findFile( $title );
		if ( $repoFile ) {
			$timestamp = null;
			$history = $repoFile->getHistory();

			if ( $history ) {
				$oldest = end( $history );
				if ( $oldest ) {
					$timestamp = $oldest->getTimestamp();
				}
			}

			if ( !$timestamp ) {
				$timestamp = $repoFile->getTimestamp();
			}

			if ( $timestamp ) {
				$unixTimestamp = (int)wfTimestamp( TS_UNIX, $timestamp );
				if ( $unixTimestamp ) {
					return $unixTimestamp;
				}
			}
		}

		// fallback
		return min( $file->getMTime(), $file->getCTime() );
	}

}
