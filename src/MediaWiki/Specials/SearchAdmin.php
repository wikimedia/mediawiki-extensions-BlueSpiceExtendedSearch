<?php

namespace BS\ExtendedSearch\MediaWiki\Specials;

class SearchAdmin extends \SpecialPage {

	public function __construct( $name = '', $restriction = '', $listed = true, $function = false, $file = '', $includable = false ) {
		parent::__construct( 'BSSearchAdmin', 'extendedsearchadmin-viewspecialpage' );
	}

	public function execute( $subPage ) {
		$this->setHeaders();

		$derivRequest = new \DerivativeRequest(
			$this->getRequest(),
			[ 'action' => 'bs-extendedsearch-stats'	]
		);

		$api = new \ApiMain( $derivRequest );
		$api->execute();
		$data = $api->getResult()->getResultData();

		$this->getOutput()->addModuleStyles( 'ext.blueSpiceExtendedSearch.SearchAdmin.styles' );
		$this->renderOverview( $data );
	}

	protected function getGroupName() {
		return 'bluespice';
	}

	protected function renderOverview( $data ) {
		$stats = $data['stats'];
		$this->getOutput()->addHTML( \Html::rawElement(
			'h2',
			[ 'class' => 'bs-es-admin-heading-backend' ],
			$this->msg( 'bs-extendedsearch-admin-heading-backend' )->plain()
		) );
		if( isset( $stats['error'] ) ) {
			$this->renderError( $stats['error'] );
		}
		else {
			$this->renderStats( $stats );
		}
	}

	public function renderError( $sErrorMessage ) {
		$this->getOutput()->addHTML( \Html::rawElement(
			'div',
			[ 'class' => 'bs-error' ],
			\Html::element(
				'span',
				[ 'class' => 'bs-es-admin-error-label' ],
				$this->msg( 'bs-extendedsearch-admin-label-error' )->plain()
			).
			\Html::element(
				'span',
				[ 'class' => 'bs-es-admin-error-message' ],
				$sErrorMessage
			)
		) );
	}

	protected function renderStats( $aBackedStats ) {
		$this->renderAllDocumentsCount( $aBackedStats['all_documents_count'] );
		$this->renderSources( $aBackedStats['sources'] );
	}

	protected function renderAllDocumentsCount( $iAllDocumentsCount ) {
		$this->getOutput()->addHTML( \Html::rawElement(
			'div',
			[],
			\Html::element(
				'span',
				[ 'class' => 'bs-es-admin-all-documents-count-label' ],
				$this->msg( 'bs-extendedsearch-admin-label-all-documents-count' )->plain()
			).
			\Html::element(
				'span',
				[ 'class' => 'bs-es-admin-all-documents-count-value' ],
				$iAllDocumentsCount
			)
		) );
	}

	protected function renderSources( $aSources ) {
		$this->getOutput()->addHTML( \Html::rawElement(
			'h3',
			[ 'class' => 'bs-es-admin-heading-sources' ],
			$this->msg( 'bs-extendedsearch-admin-heading-sources' )->plain()
		) );

		$this->getOutput()->addHTML( \Html::openElement( 'table', [
			'class' => 'bs-es-admin-table-sources contenttable'
		] ) );
		$this->renderSourceTableHeading();

		foreach ( $aSources as $sSourceKey => $aSourceStats ) {
			$this->renderSourceTableRow( $sSourceKey, $aSourceStats );
		}

		$this->getOutput()->addHTML( \Html::closeElement( 'table' ) );
	}

	protected function renderSourceTableRow( $sSourceKey, $aSourceStats ) {
		$this->getOutput()->addHTML( \Html::openElement( 'tr' ) );
		$this->getOutput()->addHTML( \Html::element(
			'th',
			[],
			$aSourceStats['label'] . " ($sSourceKey)"
 		) );

		$this->getOutput()->addHTML( \Html::element(
			'td',
			[],
			$aSourceStats['documents_count']
 		) );

		$this->getOutput()->addHTML( \Html::element(
			'td',
			[],
			$aSourceStats['pending_update_jobs']
 		) );

		$this->getOutput()->addHTML( \Html::closeElement( 'tr' ) );
	}

	protected function renderSourceTableHeading() {
		$this->getOutput()->addHTML( \Html::openElement( 'tr' ) );
		$this->getOutput()->addHTML( \Html::element(	'th' ) );

		$this->getOutput()->addHTML( \Html::element(
			'th',
			[],
			$this->msg( 'bs-extendedsearch-admin-heading-sources-documentscount' )->plain()
 		) );

		$this->getOutput()->addHTML( \Html::element(
			'th',
			[],
			$this->msg( 'bs-extendedsearch-admin-heading-pendingupdatejobs' )->plain()
 		) );

		$this->getOutput()->addHTML( \Html::closeElement( 'tr' ) );
	}
}
