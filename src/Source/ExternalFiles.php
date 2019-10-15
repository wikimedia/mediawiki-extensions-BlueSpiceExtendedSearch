<?php

namespace BS\ExtendedSearch\Source;

use BS\ExtendedSearch\Source\Crawler\ExternalFile as ExternalFileCrawler;
use BS\ExtendedSearch\Source\DocumentProvider\File as FileDocumentProvider;
use BS\ExtendedSearch\Source\Formatter\ExternalFileFormatter;

class ExternalFiles extends Files {

	/**
	 * @param Base $base
	 * @return ExternalFiles
	 */
	public static function create( $base ) {
		return new self( $base );
	}

	/**
	 *
	 * @return ExternalFileCrawler
	 */
	public function getCrawler() {
		return new ExternalFileCrawler( $this->getConfig() );
	}

	/**
	 *
	 * @return FileDocumentProvider
	 */
	public function getDocumentProvider() {
		return new FileDocumentProvider(
			$this->oDecoratedSource->getDocumentProvider()
		);
	}

	/**
	 * @return ExternalFileFormatter
	 */
	public function getFormatter() {
		return new ExternalFileFormatter( $this );
	}

	/**
	 * @return string
	 */
	public function getSearchPermission() {
		return 'extendedsearch-search-externalfile';
	}
}
