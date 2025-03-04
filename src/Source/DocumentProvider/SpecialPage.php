<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage as MediaWikiSpecialPage;

class SpecialPage extends Base {

	/**
	 *
	 * @param string $sUri
	 * @param string $documentId
	 * @param MediaWikiSpecialPage $oSpecialPage
	 *
	 * @return array
	 */
	public function getDocumentData( $sUri, string $documentId, $oSpecialPage ): array {
		$aDC = parent::getDocumentData( $sUri, $documentId, $oSpecialPage );

		$description = $oSpecialPage->getDescription();
		if ( $description instanceof Message ) {
			$description = $description->text();
		}
		return array_merge( $aDC, [
			'basename' => $oSpecialPage->getPageTitle()->getBaseText(),
			'basename_exact' => $oSpecialPage->getPageTitle()->getBaseText(),
			'extension' => 'special',
			'mime_type' => 'text/html',
			'prefixed_title' => $oSpecialPage->getPageTitle()->getPrefixedText(),
			'description' => $description,
			'namespace' => $oSpecialPage->getPageTitle()->getNamespace(),
			'namespace_text' => $oSpecialPage->getPageTitle()->getNsText()
		] );
	}
}
