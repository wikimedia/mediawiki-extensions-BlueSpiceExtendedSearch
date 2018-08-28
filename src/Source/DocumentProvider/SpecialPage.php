<?php

namespace BS\ExtendedSearch\Source\DocumentProvider;

class SpecialPage extends DecoratorBase {

	/**
	 *
	 * @param string $sUri
	 * @param \SpecialPage $oSpecialPage
	 * @return array
	 */
	public function getDataConfig( $sUri, $oSpecialPage ) {
		$aDC = $this->oDecoratedDP->getDataConfig( $sUri, $oSpecialPage );
		$aDC = array_merge( $aDC, [
			'basename' => $oSpecialPage->getPageTitle()->getBaseText(),
			'basename_exact' => $oSpecialPage->getPageTitle()->getBaseText(),
			'extension' => 'special',
			'mime_type' => 'text/html',
			'prefixed_title' => $oSpecialPage->getPageTitle()->getPrefixedText(),
			'description' => $oSpecialPage->getDescription(),
			'namespace' => $oSpecialPage->getPageTitle()->getNamespace(),
			'namespace_text' => $oSpecialPage->getPageTitle()->getNsText()
		] );

		return $aDC;
	}
}