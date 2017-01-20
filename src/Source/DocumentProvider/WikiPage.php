<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class WikiPage extends DecoratorBase {

	/**
	 *
	 * @param string $sUri
	 * @param \WikiPage $oWikiPage
	 * @return array
	 */
	public function getDataConfig( $sUri, $oWikiPage ) {
		$aDC = $this->oDecoratedDP->getDataConfig( $sUri, $oWikiPage );
		$aDC += [
			'basename' => $oWikiPage->getTitle()->getBaseText(),
			'extension' => 'wiki',
			'mime_type' => 'text/x-wiki',
			'mtime' => wfTimestamp(
				TS_ISO_8601,
				$oWikiPage->getLatest()
			),
			'ctime' => wfTimestamp(
				TS_ISO_8601,
				$oWikiPage->getRevision()->getTimestamp()
			),
			'size' => $oWikiPage->getTitle()->getLength(),
			'tags' => $this->getTagsFromCategories( $oWikiPage ),
			'prefixed_title' => $oWikiPage->getTitle()->getPrefixedText(),
			'sections' => $this->getSections( $oWikiPage ),
			'source_content' => $this->getTextContent( $oWikiPage ),
			'rendered_content' => $this->getHTMLContent( $oWikiPage ),
			'namespace' => $oWikiPage->getTitle()->getNamespace(),
			'namespace_text' => $oWikiPage->getTitle()->getNsText()
		];
		return $aDC;
	}

	/**
	 *
	 * @param \WikiPage $oWikiPage
	 */
	protected function getTagsFromCategories( $oWikiPage ) {
		$oCatTitles = $oWikiPage->getCategories();

		$aTags = [];
		foreach( $oCatTitles as $oCatTitle ) {
			if( $oCatTitle instanceof \Title ) {
				$aTags[] = $oCatTitle->getText();
			}
		}

		return $aTags;
	}

	/**
	 *
	 * @param \WikiPage $oWikiPage
	 * @return string
	 */
	protected function getTextContent( $oWikiPage ) {
		$sText = '';
		$oContent = $oWikiPage->getContent();
		if( $oContent instanceof \Content ) {
			//maybe ContentHandler::getContentText is better?
			$sText = $oContent->getTextForSearchIndex();
		}
		return $this->stripTags( $sText );
	}

	/**
	 *
	 * @param \WikiPage $oWikiPage
	 * @return string
	 */
	protected function getHTMLContent( $oWikiPage ) {
		$sHtml = '';
		$oParserOutput = $oWikiPage->getContent()->getParserOutput( $oWikiPage->getTitle() );
		$sHtml = $oParserOutput->getText();
		return $this->stripTags( $sHtml );
	}

	/**
	 *
	 * @param \WikiPage $oWikiPage
	 * @return array
	 */
	protected function getSections( $oWikiPage ) {
		$aSections = [];
		$oParserOutput = $oWikiPage->getContent()->getParserOutput( $oWikiPage->getTitle() );
		$aRawSections = $oParserOutput->getSections();
		foreach( $aRawSections as $aRawSection ) {
			$aSections[] = $aRawSection['anchor'];
		}
		return $aSections;
	}

	protected function stripTags( $sText ) {
		$sText = strip_tags( $sText );
		$sText = preg_replace( '/<!--(.|\s)*?-->/', '', $sText );
		return $sText;
	}
}