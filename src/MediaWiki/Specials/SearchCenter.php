<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

class SearchCenter extends \SpecialPage {

	public function __construct( $name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false ) {
		parent::__construct( 'BSSearchCenter' );
	}

	public function execute( $subPage ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->addModules( "ext.blueSpiceExtendedSearch.SearchCenter" );

		$oSearchField = new \OOUI\TextInputWidget([
			'placeholder' => wfMessage( 'bs-extendedsearch-search-input-placeholder' )->plain(),
			'id' => 'bs-es-tf-search',
			'infusable' => true
		]);
		$oButton = new \OOUI\ButtonWidget([
			'id' => 'bs-es-btn-search',
			'icon' => 'search',
			'infusable' => true
		]);
		$oFieldLayout = new \OOUI\ActionFieldLayout( $oSearchField, $oButton );

		$oLocalBackend = \BS\ExtendedSearch\Backend::instance( 'local' );
		$aSourceConfig = [];
		foreach( $oLocalBackend->getSources() as $sSourceKey => $oSource ) {
			$aSourceConfig[$sSourceKey] = new \stdClass(); //In some future
			//there might be additional configs per source. ATM we only need
			//the key
		}

		$out->enableOOUI();
		$out->addHTML( $oFieldLayout );
		$out->addHTML( \Html::element( 'div', [ 'id' => 'bs-es-results' ] ) );
		$out->addJsConfigVars( 'bsgESSources', $aSourceConfig );
	}

	protected function getGroupName() {
		return 'bluespice';
	}
}
